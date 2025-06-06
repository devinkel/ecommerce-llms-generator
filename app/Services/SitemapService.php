<?php
declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * SitemapService
 *
 * Responsibilities:
 * 1. Fetch the sitemap.xml from a given URL.
 * 2. Parse the XML and filter URLs based on provided regex patterns.
 * 3. Return a Collection of ['type' => string, 'url' => string] items.
 *
 * Extracting this logic from the controller adheres to the Single Responsibility Principle
 * and makes the code easier to test and maintain.
 */
class SitemapService
{
    private Client $httpClient;

    public function __construct()
    {
        // Use Guzzle with a 10-second timeout for sitemap requests
        $this->httpClient = new Client(['timeout' => 10]);
    }

    /**
     * extractRequests
     *
     * @param string $sitemapBaseUrl The base URL to the sitemap (without trailing slash)
     * @param array<string,string> $patterns Associative array of ['type' => regexPattern]
     * @return Collection<int,array{type:string,url:string}>
     *
     * @throws \Exception If fetching or parsing the sitemap fails.
     */
    public function extractRequests(string $sitemapBaseUrl, array $patterns): Collection
    {
        $sitemapUrl = $sitemapBaseUrl;
        $requests   = collect();

        // Fetch sitemap XML
        $response = $this->httpClient->get($sitemapUrl);
        $xml      = simplexml_load_string((string)$response->getBody());

        if ($xml === false) {
            throw new \Exception('Sitemap XML inválido.');
        }

        // Iterate through each <url><loc> entry
        foreach ($xml->url as $entry) {
            $link = (string)$entry->loc;

            // Check each pattern; push the first match
            foreach ($patterns as $type => $pattern) {
                if (!empty($pattern) && @preg_match($pattern, $link)) {
                    $requests->push([
                        'type' => $type,
                        'url'  => $link,
                    ]);
                    break;
                }
            }
        }

        return $requests;
    }
}
