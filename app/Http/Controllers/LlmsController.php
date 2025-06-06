<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Services\SitemapService;
use App\Services\PageCrawlerService;

use Validator;

/**
 * LlmsController
 *
 * Responsibilities:
 * 1. Validate incoming HTTP request parameters.
 * 2. Delegate sitemap parsing to SitemapService.
 * 3. Delegate page crawling/extraction to PageCrawlerService.
 * 4. Assemble Markdown output and return view.
 *
 * By offloading crawling logic to services, this controller remains slim
 * and focused on orchestration and request/response handling.
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
     *
     * No business logic here—simply returns the Blade (or plain) view.
     */
    public function index()
    {
        return View::make('home');
    }

    /**
     * Handle the POST /generate request:
     * 1. Validate 'url' and optional regex patterns for each type.
     * 2. Call SitemapService::extractRequests() to get URLs by type.
     * 3. If no URLs match, return error to view.
     * 4. Call PageCrawlerService::crawlAll() to fetch & parse pages concurrently.
     * 5. Format results into Markdown and pass to view.
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

        // Remove trailing slash from sitemap URL
        $baseUrl = rtrim($data['url'], '/');

        // Step 2: Build pattern array
        $patterns = [
            'Produtos'    => $data['pattern_produtos']   ?? '',
            'Categorias'  => $data['pattern_categorias'] ?? '',
            'Links Úteis' => $data['pattern_uteis']      ?? '',
        ];

        // Step 3: Delegate sitemap parsing to service
        try {
            $requestItems = $this->sitemapService->extractRequests($baseUrl, $patterns);
        } catch (\Throwable $e) {
            return View::make('home', [
                'output' => null,
                'error'  => "Erro ao acessar sitemap.xml: {$e->getMessage()}",
            ]);
        }

        // If no URLs matched patterns, early return with error
        if ($requestItems->isEmpty()) {
            return View::make('home', [
                'output' => null,
                'error'  => 'Nenhuma URL do sitemap corresponde aos padrões informados.',
            ]);
        }

        // Step 4: Delegate page crawling and structured extraction
        $results = $this->crawlerService->crawlAll($requestItems->toArray());

        // Step 5: Build Markdown output
        $urlFormatted = substr($baseUrl, 0, strrpos($baseUrl, '/'));
        $markdown   = "# E-commerce: {$urlFormatted} \n\n";

        if (!empty($results['Produtos'])) {
            $markdown .= "## Produtos \n\n";
            foreach ($results['Produtos'] as $prod) {
                $markdown .= "- **Título:** {$prod['title']}  \n";
                $markdown .= "  **URL:** {$prod['url']}  \n";

                if (!empty($prod['price'])) {
                    $formattedPrice = number_format((float)$prod['price'], 2, ',', '.');
                    $markdown .= "  **Preço:** R$ {$formattedPrice}  \n";
                }
                if (!empty($prod['currency'])) {
                    $markdown .= "  **Moeda:** {$prod['currency']}  \n";
                }
                if (!empty($prod['availability'])) {
                    $markdown .= "  **Disponibilidade:** {$prod['availability']}  \n";
                }
                if (!empty($prod['condition'])) {
                    $markdown .= "  **Condição:** {$prod['condition']}  \n";
                }
                if (!empty($prod['returnDays'])) {
                    $markdown .= "  **Prazo para devolução:** {$prod['returnDays']} dias  \n";
                }
                if (!empty($prod['returnFees'])) {
                    $markdown .= "  **Frete da devolução:** {$prod['returnFees']}  \n";
                }
                if (!empty($prod['returnMethod'])) {
                    $markdown .= "  **Forma de devolução:** {$prod['returnMethod']}  \n";
                }
                $markdown .= "\n";
            }
        }

        if (!empty($results['Categorias'])) {
            $markdown .= "## Categorias\n\n";
            foreach ($results['Categorias'] as $cat) {
                $markdown .= "- **Nome:** {$cat['name']}  \n";
                $markdown .= "  **URL:** {$cat['url']}  \n\n";
            }
        }

        if (!empty($results['Links Úteis'])) {
            $markdown .= "## Links Úteis\n\n";
            foreach ($results['Links Úteis'] as $link) {
                $markdown .= "- **Título da página:** {$link['title']}  \n";
                $markdown .= "  **URL:** {$link['url']}  \n\n";
            }
        }

        return View::make('home', [
            'output' => $markdown,
            'error'  => null,
        ]);
    }
}
