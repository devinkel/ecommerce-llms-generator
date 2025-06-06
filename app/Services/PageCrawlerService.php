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
 * Responsibilities:
 * 1. Perform concurrent HTTP GET requests to each URL provided by SitemapService.
 * 2. Parse HTML for each page using Symfony DomCrawler.
 * 3. Extract structured data for “Produtos”, “Categorias” and “Links Úteis”.
 * 4. Enrich product data via microdata (schema.org) and JSON-LD fallbacks.
 * 5. Map schema.org URIs to human-readable labels via config.
 *
 * Separating this from the controller keeps the HTTP concurrency and parsing
 * logic in one place, facilitating unit testing and future refactoring.
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

    /**
     * crawlAll
     *
     * @param array<int,array{type:string,url:string}> $requestItems
     * @return array<string,array<int,array<string,mixed>>> Result buckets keyed by type
     *
     * Uses Guzzle’s Pool for concurrency. Returns an array:
     * [
     *   'Produtos'    => [ ['title' => ..., 'url' => ..., ...], ... ],
     *   'Categorias'  => [ ['name' => ..., 'url' => ...], ... ],
     *   'Links Úteis' => [ ['title' => ..., 'url' => ...], ... ],
     * ]
     */
    public function crawlAll(array $requestItems): array
    {
        $results = [
            'Produtos'    => [],
            'Categorias'  => [],
            'Links Úteis' => [],
        ];

        $requestsCollection = collect($requestItems);

        // Generator to yield GuzzleRequest instances
        $requestGenerator = function () use ($requestsCollection) {
            foreach ($requestsCollection as $item) {
                yield new GuzzleRequest('GET', $item['url'], ['X-Type' => $item['type']]);
            }
        };

        $pool = new Pool($this->httpClient, $requestGenerator(), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index) use (&$results, $requestsCollection) {
                $this->handleFulfilled($response, $index, $requestsCollection, $results);
            },
            'rejected'    => function ($reason, $index) {
                // Log rejected requests for later analysis
                Log::warning('Requisição recusada no Pool', ['index' => $index, 'erro' => (string)$reason]);
            },
        ]);

        // Wait for all requests to complete
        $pool->promise()->wait();

        return $results;
    }

    /**
     * handleFulfilled
     *
     * Called for each successful response in the Pool.
     */
    private function handleFulfilled($response, int $index, Collection $requestsCollection, array &$results): void
    {
        try {
            $html    = (string)$response->getBody();
            $crawler = new Crawler($html);
            $item    = $requestsCollection[$index];
            $type    = $item['type'];
            $url     = $item['url'];

            // Extract <title>
            $titleNode = $crawler->filter('title')->first();
            $title     = $titleNode ? trim($titleNode->text()) : '';

            if ($type === 'Produtos') {
                $results['Produtos'][] = $this->extractProductData($crawler, $url, $title);
            } elseif ($type === 'Categorias') {
                $results['Categorias'][] = [
                    'name' => $title,
                    'url'  => $url,
                ];
            } elseif ($type === 'Links Úteis') {
                $results['Links Úteis'][] = [
                    'title' => $title,
                    'url'   => $url,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao processar item do Pool', [
                'url'  => $requestsCollection[$index]['url'] ?? '',
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * extractProductData
     *
     * Parses microdata (schema.org) and falls back to JSON-LD when needed.
     * Returns a structured array with all product fields.
     */
    private function extractProductData(Crawler $crawler, string $url, string $fallbackTitle): array
    {
        // Initialize fields
        $title        = $fallbackTitle;
        $price        = null;
        $currency     = null;
        $availability = null;
        $condition    = null;
        $returnDays   = null;
        $returnFees   = null;
        $returnMethod = null;

        // 1) Try microdata (schema.org/Offer)
        if ($crawler->filter('[itemtype="https://schema.org/Offer"]')->count() > 0) {
            $offerNode    = $crawler->filter('[itemtype="https://schema.org/Offer"]')->first();
            $price        = $offerNode->filter('[itemprop="price"]')->first()?->attr('content')        ?? null;
            $currency     = $offerNode->filter('[itemprop="priceCurrency"]')->first()?->attr('content') ?? null;
            $availability = $offerNode->filter('[itemprop="availability"]')->first()?->attr('content')   ?? null;
            $condition    = $offerNode->filter('[itemprop="itemCondition"]')->first()?->attr('content') ?? null;
            $returnDays   = $offerNode->filter('[itemprop="merchantReturnDays"]')->first()?->attr('content') ?? null;
        }

        // 2) Fallback to JSON-LD if any primary field is missing
        if (!$price || !$currency || !$availability) {
            $jsonLdRaw = $crawler->filter('script[type="application/ld+json"]')->first()?->getNode(0)?->nodeValue;

            if ($jsonLdRaw) {
                $jsonSanitized = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonLdRaw));
                $data          = json_decode($jsonSanitized, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // If @graph exists, find the Product node
                    if (isset($data['@graph'])) {
                        foreach ($data['@graph'] as $graphItem) {
                            if (($graphItem['@type'] ?? '') === 'Product') {
                                $data = $graphItem;
                                break;
                            }
                        }
                    }
                    // If still a Product with offers, extract fields
                    if (($data['@type'] ?? '') === 'Product' && isset($data['offers'])) {
                        $offers   = $data['offers'];
                        $title    = $data['name']        ?? $title;
                        $price    = $offers['price']     ?? $price;
                        $currency = $offers['priceCurrency'] ?? $currency;
                        $availability = $offers['availability'] ?? $availability;
                        $condition    = $offers['itemCondition']  ?? $condition;

                        if (isset($offers['hasMerchantReturnPolicy'])) {
                            $policy      = $offers['hasMerchantReturnPolicy'];
                            $returnDays  = $policy['merchantReturnDays'] ?? $returnDays;
                            $returnFees  = $policy['returnFees']         ?? null;
                            $returnMethod= $policy['returnMethod']       ?? null;
                        }
                    }
                } else {
                    Log::error('Erro ao decodificar JSON-LD', [
                        'json' => $jsonLdRaw,
                        'erro' => json_last_error_msg(),
                        'url'  => $url,
                    ]);
                }
            } else {
                Log::warning('Nenhum JSON-LD encontrado na página', ['url' => $url]);
            }
        }

        // Map schema.org URIs to human-readable labels (if available)
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
