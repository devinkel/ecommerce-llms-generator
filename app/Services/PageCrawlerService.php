<?php
declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

/**
 * PageCrawlerService
 *
 * 1. Fetch URLs concurrently via Guzzle Pool.
 * 2. Extract product data (microdata + JSON-LD).
 * 3. Extract generic SEO tags from HTML.
 */
class PageCrawlerService
{
    private Client $httpClient;
    private int $concurrency;
    private array $availabilityLabels;
    private array $conditionLabels;

    public function __construct()
    {
        $this->httpClient        = new Client(['timeout' => 30]);
        $this->concurrency       = config('llms.concurrency', 20);
        $this->availabilityLabels = config('llms.availability_labels', []);
        $this->conditionLabels    = config('llms.condition_labels', []);
    }

    public function crawlAll(array $requestItems): array
    {
        $results = [
            'Produtos'    => [],
            'Categorias'  => [],
            'Links Úteis' => [],
        ];

        $collection = collect($requestItems);

        $generator = function () use ($collection) {
            foreach ($collection as $item) {
                yield new GuzzleRequest('GET', $item['url'], ['X-Type' => $item['type']]);
            }
        };

        $pool = new Pool($this->httpClient, $generator(), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index) use (&$results, $collection) {
                $this->handleFulfilled($response, $index, $collection, $results);
            },
            'rejected'    => function ($reason, $index) {
                Log::warning('Requisição recusada', ['index' => $index, 'erro' => (string)$reason]);
            },
        ]);

        $pool->promise()->wait();

        return $results;
    }

    private function handleFulfilled($response, int $index, Collection $requests, array &$results): void
    {
        try {
            $html    = (string)$response->getBody();
            $crawler = new Crawler($html);
            $item    = $requests[$index];
            $type    = $item['type'];
            $url     = $item['url'];

            // Base data
            $title = trim($crawler->filter('title')->first()?->text() ?? '');

            if ($type === 'Produtos') {
                $data = $this->extractProductData($crawler, $url, $title);
            } else {
                $data = ['title' => $title, 'url' => $url];
            }

            // Add generic SEO tags
            $generic = $this->extractGenericTags($crawler);
            $data    = array_merge($data, $generic);

            $results[$type][] = $data;
        } catch (\Throwable $e) {
            Log::error('Erro no handleFulfilled', [
                'url' => $requests[$index]['url'] ?? '',
                'erro'=> $e->getMessage(),
            ]);
        }
    }

    private function extractGenericTags(Crawler $crawler): array
    {
        $metaDesc = $crawler
            ->filter('meta[name="description"]')
            ->first()
            ?->attr('content')
            ?? '';

        $metaDesc = trim(strip_tags($metaDesc));

        return [
            'metaDescription' => $metaDesc
        ];
    }

    private function extractProductData(Crawler $crawler, string $url, string $fallbackTitle): array
    {
        $title        = $fallbackTitle;
        $price        = null;
        $currency     = null;
        $availability = null;
        $condition    = null;
        $returnDays   = null;
        $returnFees   = null;
        $returnMethod = null;

        // 1) Microdata schema.org/Offer
        if ($crawler->filter('[itemtype="https://schema.org/Offer"]')->count() > 0) {
            $offer = $crawler->filter('[itemtype="https://schema.org/Offer"]')->first();
            $price        = $offer->filter('[itemprop="price"]')->first()?->attr('content')        ?? $price;
            $currency     = $offer->filter('[itemprop="priceCurrency"]')->first()?->attr('content') ?? $currency;
            $availability = $offer->filter('[itemprop="availability"]')->first()?->attr('content')   ?? $availability;
            $condition    = $offer->filter('[itemprop="itemCondition"]')->first()?->attr('content') ?? $condition;
            $returnDays   = $offer->filter('[itemprop="merchantReturnDays"]')->first()?->attr('content') ?? $returnDays;
        }

        // 2) Fallback JSON-LD
        if (!$price || !$currency || !$availability) {
            $jsonLdRaw = $crawler->filter('script[type="application/ld+json"]')->first()?->getNode(0)?->nodeValue;
            if ($jsonLdRaw) {
                $json = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonLdRaw));
                $data = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($data['@graph'])) {
                        foreach ($data['@graph'] as $g) {
                            if (($g['@type'] ?? '') === 'Product') {
                                $data = $g;
                                break;
                            }
                        }
                    }
                    if (($data['@type'] ?? '') === 'Product' && isset($data['offers'])) {
                        $offers   = $data['offers'];
                        $title    = $data['name']        ?? $title;
                        $price    = $offers['price']     ?? $price;
                        $currency = $offers['priceCurrency'] ?? $currency;
                        $availability = $offers['availability'] ?? $availability;
                        $condition    = $offers['itemCondition']  ?? $condition;
                        if (isset($offers['hasMerchantReturnPolicy'])) {
                            $policy     = $offers['hasMerchantReturnPolicy'];
                            $returnDays = $policy['merchantReturnDays'] ?? $returnDays;
                            $returnFees = $policy['returnFees']         ?? $returnFees;
                            $returnMethod= $policy['returnMethod']       ?? $returnMethod;
                        }
                    }
                } else {
                    Log::error('JSON-LD inválido', ['erro'=>json_last_error_msg(), 'url'=>$url]);
                }
            } else {
                Log::warning('JSON-LD não encontrado', ['url'=>$url]);
            }
        }

        $availability = $this->availabilityLabels[$availability] ?? $availability;
        $condition    = $this->conditionLabels[$condition]    ?? $condition;

        return [
            'title'         => $title,
            'url'           => $url,
            'price'         => $price,
            'currency'      => $currency,
            'availability'  => $availability,
            'condition'     => $condition,
            'returnDays'    => $returnDays,
            'returnFees'    => $returnFees,
            'returnMethod'  => $returnMethod,
        ];
    }
}
