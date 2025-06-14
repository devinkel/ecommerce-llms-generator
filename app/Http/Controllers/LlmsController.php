<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Services\SitemapService;
use App\Services\PageCrawlerService;

/**
 * LlmsController
 *
 * Responsibilities:
 * 1. Validate incoming HTTP request parameters.
 * 2. Delegate sitemap parsing to SitemapService.
 * 3. Delegate page crawling/extraction to PageCrawlerService.
 * 4. Assemble Markdown output and return view.
 */
class LlmsController extends Controller
{
    private SitemapService $sitemapService;
    private PageCrawlerService $crawlerService;

    public function __construct(
        SitemapService $sitemapService,
        PageCrawlerService $crawlerService
    ) {
        $this->sitemapService = $sitemapService;
        $this->crawlerService = $crawlerService;
    }

    /**
     * Show the form/home view.
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Handle the POST /generate request.
     */
    public function generate(Request $request)
    {
        // Step 1: Basic validation
        $data = $this->validate($request, [
            'url'                => 'required|url',
            'pattern_produtos'   => 'nullable|string',
            'pattern_categorias' => 'nullable|string',
            'pattern_uteis'      => 'nullable|string',
        ]);

        $baseUrl  = rtrim($data['url'], '/');
        $patterns = [
            'Produtos'    => $data['pattern_produtos']   ?? '',
            'Categorias'  => $data['pattern_categorias'] ?? '',
            'Links Úteis' => $data['pattern_uteis']      ?? '',
        ];

        // Step 2: Extract URLs from sitemap
        try {
            $requestItems = $this->sitemapService->extractRequests($baseUrl, $patterns);
        } catch (\Throwable $e) {
            return view('home', [
                'output' => null,
                'error'  => "Erro ao acessar sitemap.xml: {$e->getMessage()}",
            ]);
        }

        if ($requestItems->isEmpty()) {
            return view('home', [
                'output' => null,
                'error'  => 'Nenhuma URL do sitemap corresponde aos padrões informados.',
            ]);
        }

        // Step 3: Crawl pages and extract data
        $results = $this->crawlerService->crawlAll($requestItems->toArray());

        // Step 4: Build Markdown output
        $markdown = "# E-commerce: {$baseUrl}\n\n";

        foreach (['Produtos', 'Categorias', 'Links Úteis'] as $type) {
            if (empty($results[$type])) {
                continue;
            }
            $markdown .= "## {$type}\n\n";
            foreach ($results[$type] as $item) {
                $markdown .= "- **Título:** {$item['title']}\n";
                $markdown .= "  **URL:** {$item['url']}\n";

                // Produto-specific fields
                if ($type === 'Produtos') {
                    if (!empty($item['price'])) {
                        $formattedPrice = number_format((float)$item['price'], 2, ',', '.');
                        $markdown        .= "  **Preço:** R$ {$formattedPrice}\n";
                    }
                    if (!empty($item['currency'])) {
                        $markdown .= "  **Moeda:** {$item['currency']}\n";
                    }
                    if (!empty($item['availability'])) {
                        $markdown .= "  **Disponibilidade:** {$item['availability']}\n";
                    }
                    if (!empty($item['condition'])) {
                        $markdown .= "  **Condição:** {$item['condition']}\n";
                    }
                    if (!empty($item['returnDays'])) {
                        $markdown .= "  **Prazo de devolução:** {$item['returnDays']} dias\n";
                    }
                    if (!empty($item['returnFees'])) {
                        $markdown .= "  **Taxa de devolução:** {$item['returnFees']}\n";
                    }
                    if (!empty($item['returnMethod'])) {
                        $markdown .= "  **Método de devolução:** {$item['returnMethod']}\n";
                    }
                }

                // Generic SEO tags
                if (!empty($item['metaDescription'])) {
                    $markdown .= "  **Descrição:** {$item['metaDescription']}\n";
                }

                $markdown .= "\n";
            }
        }

        return view('home', [
            'output' => $markdown,
            'error'  => null,
        ]);
    }
}
