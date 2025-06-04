<?php

// Projeto Lumen com suporte a views e geração de llms.txt via sitemap.xml com padrões dinâmicos e categorias personalizadas

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Support\Facades\View;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

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
        $sitemapUrl = $url;
        $patternProdutos = $request->input('pattern_produtos');
        $patternCategorias = $request->input('pattern_categorias');
        $patternUteis = $request->input('pattern_uteis');

        $client = new Client(['timeout' => 10]);

        try {
            $response = $client->get($sitemapUrl);
            $xml = simplexml_load_string((string) $response->getBody());
        } catch (\Exception $e) {
            return View::make('home', ['output' => "Erro ao acessar sitemap.xml: {$e->getMessage()}"]);
        }

        $requests = [];
        $results = [
            'Produtos' => [],
            'Categorias' => [],
            'Links Úteis' => [],
        ];

        foreach ($xml->url as $entry) {
            $link = (string) $entry->loc;

            if (!empty($patternProdutos) && @preg_match($patternProdutos, $link)) {
                $requests[] = ['type' => 'Produtos', 'url' => $link];
            } elseif (!empty($patternCategorias) && @preg_match($patternCategorias, $link)) {
                $requests[] = ['type' => 'Categorias', 'url' => $link];
            } elseif (!empty($patternUteis) && @preg_match($patternUteis, $link)) {
                $requests[] = ['type' => 'Links Úteis', 'url' => $link];
            }
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
                    $crawler = new DomCrawler($html);
                    $title = $crawler->filter('title')->first()->text('');
                    $item = $requests[$index];

                    if ($item['type'] === 'Produtos') {
                        $priceNode = $crawler->filter('[itemprop="price"]')->first();
                        $price = $priceNode->count() ? $priceNode->attr('content') : null;
                        $label = $price ? "$title - R$ $price" : $title;
                    } else {
                        $label = $title;
                    }

                    $results[$item['type']][] = [$label, $item['url']];
                } catch (\Exception $e) {
                    // Ignorar
                }
            },
            'rejected' => function () {
                // Ignorar falhas
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        $output = "# Loja Virtual\n\n> Gerado automaticamente via Lumen\n\n";

        foreach (["Produtos", "Categorias", "Links Úteis"] as $section) {
            if (count($results[$section])) {
                $output .= "## {$section}\n";
                foreach ($results[$section] as [$label, $link]) {
                    $output .= "- [{$label}]({$link})\n";
                }
                $output .= "\n";
            }
        }

        return View::make('home', ['output' => $output]);
    }
}
