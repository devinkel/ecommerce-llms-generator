<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Support\Facades\View;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LlmsController extends Controller
{
    public function index()
    {
        return View::make('home');
    }

    public function generate(Request $request)
    {
        set_time_limit(0);

        $url = rtrim($request->input('url'), '/');
        $patterns = [
        'Produtos' => $request->input('pattern_produtos'),
        'Categorias' => $request->input('pattern_categorias'),
        'Links Úteis' => $request->input('pattern_uteis')
        ];

        $client = new Client(['timeout' => 10]);

        try {
            $response = $client->get($url);
            $xml = simplexml_load_string((string) $response->getBody());
        } catch (\Exception $e) {
            return View::make('home', [
            'output' => null,
            'error' => "Erro ao acessar sitemap.xml: {$e->getMessage()}"
            ]);
        }

        $requests = collect();
        $results = [
        'Produtos' => [],
        'Categorias' => [],
        'Links Úteis' => []
        ];

        $availabilityLabels = [
            'https://schema.org/InStock' => 'Em estoque',
            'https://schema.org/OutOfStock' => 'Indisponível',
            'https://schema.org/PreOrder' => 'Pré-venda',
            'https://schema.org/SoldOut' => 'Esgotado',
            'https://schema.org/Discontinued' => 'Descontinuado',
        ];

        $conditionLabels = [
            'https://schema.org/NewCondition' => 'Novo',
            'https://schema.org/UsedCondition' => 'Usado',
            'https://schema.org/RefurbishedCondition' => 'Recondicionado',
            'https://schema.org/DamagedCondition' => 'Com avarias',
        ];

        foreach ($xml->url as $entry) {
            $link = (string) $entry->loc;

            foreach ($patterns as $type => $pattern) {
                if (!empty($pattern) && @preg_match($pattern, $link)) {
                    $requests->push(['type' => $type, 'url' => $link]);
                    break;
                }
            }
        }

        if ($requests->isEmpty()) {
            return View::make('home', [
            'output' => null,
            'error' => 'Nenhuma URL do sitemap corresponde aos padrões informados.'
            ]);
        }

        $requestGenerator = function () use ($requests) {
            foreach ($requests as $item) {
                yield new GuzzleRequest('GET', $item['url'], ['X-Type' => $item['type']]);
            }
        };
        $pool = new Pool($client, $requestGenerator(), [
            'concurrency' => 20,
            'fulfilled' => function ($response, $index) use (&$results, $requests) {
                try {
                    $html = (string) $response->getBody();
                    $crawler = new Crawler($html);
                    $title = Str::limit(trim($crawler->filter('title')->first()->text('')), 350);

                    $item = $requests[$index];
                    $url = $item['url'];

                    if ($item['type'] === 'Produtos') {
                        // Inicializa com null
                        $price = $currency = $availability = $condition = $returnDays = $returnFees = $returnMethod = null;

                        // Tenta extrair do HTML com microdata (schema.org)
                        if ($crawler->filter('[itemtype="https://schema.org/Offer"]')->count() > 0) {
                            $offer = $crawler->filter('[itemtype="https://schema.org/Offer"]')->first();
                            $price = optional($offer->filter('[itemprop="price"]')->first())->attr('content');
                            $currency = optional($offer->filter('[itemprop="priceCurrency"]')->first())->attr('content');
                            $availability = optional($offer->filter('[itemprop="availability"]')->first())->attr('content');
                            $condition = optional($offer->filter('[itemprop="itemCondition"]')->first())->attr('content');
                            $returnDays = optional($offer->filter('[itemprop="merchantReturnDays"]')->first())->attr('content');
                        }

                        // Tenta complementar com JSON-LD apenas se necessário
                        if (!$price || !$currency || !$availability) {
                            $jsonLdRaw = $crawler->filter('script[type="application/ld+json"]')->first()?->getNode(0)?->nodeValue;

                            if ($jsonLdRaw) {
                                $jsonSanitized = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonLdRaw));
                                $data = json_decode($jsonSanitized, true);

                                if (json_last_error() === JSON_ERROR_NONE) {
                                    if (isset($data['@graph'])) {
                                        foreach ($data['@graph'] as $graphItem) {
                                            if (($graphItem['@type'] ?? '') === 'Product') {
                                                $data = $graphItem;
                                                break;
                                            }
                                        }
                                    }

                                    if (($data['@type'] ?? '') === 'Product' && isset($data['offers'])) {
                                        $offers = $data['offers'];

                                        $title = $data['name'] ?? $title;
                                        $price = $offers['price'] ?? $price;
                                        $currency = $offers['priceCurrency'] ?? $currency;
                                        $availability = $offers['availability'] ?? $availability;
                                        $condition = $offers['itemCondition'] ?? $condition;

                                        if (isset($offers['hasMerchantReturnPolicy'])) {
                                            $policy = $offers['hasMerchantReturnPolicy'];
                                            $returnDays = $policy['merchantReturnDays'] ?? $returnDays;
                                            $returnFees = $policy['returnFees'] ?? null;
                                            $returnMethod = $policy['returnMethod'] ?? null;
                                        }
                                    }
                                } else {
                                    \Log::error('Erro ao decodificar JSON-LD', [
                                        'json' => $jsonLdRaw,
                                        'erro' => json_last_error_msg(),
                                        'url' => $url,
                                    ]);
                                }
                            } else {
                                \Log::warning('Nenhum JSON-LD encontrado na página', ['url' => $url]);
                            }
                        }

                        $availabilityLabels = [
                            'https://schema.org/InStock' => 'Em estoque',
                            'https://schema.org/OutOfStock' => 'Indisponível',
                            'https://schema.org/PreOrder' => 'Pré-venda',
                            'https://schema.org/SoldOut' => 'Esgotado',
                            'https://schema.org/Discontinued' => 'Descontinuado',
                        ];

                        $conditionLabels = [
                            'https://schema.org/NewCondition' => 'Novo',
                            'https://schema.org/UsedCondition' => 'Usado',
                            'https://schema.org/RefurbishedCondition' => 'Recondicionado',
                            'https://schema.org/DamagedCondition' => 'Com avarias',
                        ];

                        $availability = $availabilityLabels[$availability] ?? $availability;
                        $condition = $conditionLabels[$condition] ?? $condition;

                        $results['Produtos'][] = compact(
                            'title', 'url', 'price', 'currency',
                            'availability', 'condition', 'returnDays',
                            'returnFees', 'returnMethod'
                        );
                    } elseif ($item['type'] === 'Categorias') {
                        $results['Categorias'][] = ['name' => $title, 'url' => $url];
                    } elseif ($item['type'] === 'Links Úteis') {
                        $results['Links Úteis'][] = ['title' => $title, 'url' => $url];
                    }
                } catch (\Exception $e) {
                    \Log::error('Erro ao processar item do Pool', [
                        'url' => $requests[$index]['url'] ?? '',
                        'erro' => $e->getMessage()
                    ]);
                }
            },
            'rejected' => function () {
                // ignora falhas de requisição
            },
        ]);

        $pool->promise()->wait();

        $url_formated = substr($url, 0, strrpos($url, '/'));
        $output = "# E-commerce: {$url_formated} \n\n";

        if (!empty($results['Produtos'])) {
            $output .= "## Produtos \n\n";
            foreach ($results['Produtos'] as $prod) {
                $output .= "- **Título:** {$prod['title']}  \n";
                $output .= "  **URL:** {$prod['url']}  \n";

                if (isset($prod['price'])) {
                    $formattedPrice = number_format($prod['price'], 2, ',', '.');
                    $output .= "  **Preço:** R$ {$formattedPrice}  \n";
                }

                if (isset($prod['currency'])) {
                    $output .= "  **Moeda:** {$prod['currency']}  \n";
                }

                if (isset($prod['availability'])) {
                    $output .= "  **Disponibilidade:** {$prod['availability']}  \n";
                }

                if (isset($prod['condition'])) {
                    $output .= "  **Condição:** {$prod['condition']}  \n";
                }

                if (isset($prod['returnDays'])) {
                    $output .= "  **Prazo para devolução:** {$prod['returnDays']} dias  \n";
                }

                if (isset($prod['returnFees'])) {
                    $output .= "  **Frete da devolução:** {$prod['returnFees']}  \n";
                }

                if (isset($prod['returnMethod'])) {
                    $output .= "  **Forma de devolução:** {$prod['returnMethod']}  \n";
                }

                $output .= "\n";
            }
        }

        if (!empty($results['Categorias'])) {
            $output .= "## Categorias\n\n";
            foreach ($results['Categorias'] as $cat) {
                $output .= "- **Nome:** {$cat['name']}  \n";
                $output .= "  **URL:** {$cat['url']}  \n\n";
            }
        }

        if (!empty($results['Links Úteis'])) {
            $output .= "## Links Úteis\n\n";
            foreach ($results['Links Úteis'] as $link) {
                $output .= "- **Título da página:** {$link['title']}  \n";
                $output .= "  **URL:** {$link['url']}  \n\n";
            }
        }

        return View::make('home', ['output' => $output, 'error' => null]);
    }
}
