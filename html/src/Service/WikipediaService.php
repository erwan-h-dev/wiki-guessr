<?php

namespace App\Service;

use Exception;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class WikipediaService
{
    private const API_ENDPOINT = 'https://fr.wikipedia.org/w/api.php';
    private const TIMEOUT = 10;
    private const CACHE_TTL_PAGE = 86400; // 24 heures pour les pages complètes
    private const CACHE_TTL_EXTRACT = 86400; // 24 heures pour les extraits

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get the content of a Wikipedia page
     *
     * @param string $title
     * @return array{title: string, displaytitle: string, content: string}
     * @throws InvalidArgumentException
     */
    public function getPage(string $title): array
    {
        $cacheKey = $this->generateCacheKey('page', $title);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($title) {
            $item->expiresAfter(self::CACHE_TTL_PAGE);

            try {
                $response = $this->httpClient->request('GET', self::API_ENDPOINT, [
                    'query' => [
                        'action' => 'parse',
                        'page' => $title,
                        'prop' => 'text',
                        'format' => 'json',
                    ],
                    'timeout' => self::TIMEOUT,
                ]);

                $data = $response->toArray();

                if (isset($data['error'])) {
                    $this->logger->error('Wikipedia API error', [
                        'title' => $title,
                        'error' => $data['error'],
                    ]);
                    throw new RuntimeException(sprintf('Page "%s" not found: %s', $title, $data['error']['info'] ?? 'Unknown error'));
                }

                if (!isset($data['parse']['text'])) {
                    throw new RuntimeException(sprintf('No content found for page "%s"', $title));
                }

                $this->logger->info('Wikipedia page fetched from API (cached)', ['title' => $title]);

                return [
                    'title' => $data['parse']['title'] ?? $title,
                    'displaytitle' => $data['parse']['displaytitle'] ?? $title,
                    'content' => $data['parse']['text'],
                ];

            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Wikipedia API transport error', [
                    'title' => $title,
                    'message' => $e->getMessage(),
                ]);
                throw new RuntimeException(sprintf('Error fetching page "%s": %s', $title, $e->getMessage()), 0, $e);
            }
        });
    }

    /**
     * Get a short extract of a Wikipedia page for preview
     *
     * @return array{title: string, extract: string, thumbnail: string|null}
     * @throws Exception if the page does not exist or an error occurs
     */
    public function getPageExtract(string $title): array
    {
        $cacheKey = $this->generateCacheKey('extract', $title);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($title) {
            $item->expiresAfter(self::CACHE_TTL_EXTRACT);

            try {
                $response = $this->httpClient->request('GET', self::API_ENDPOINT, [
                    'query' => [
                        'action' => 'query',
                        'format' => 'json',
                        'prop' => 'extracts|pageimages',
                        'exintro' => true,
                        'explaintext' => true,
                        'exsentences' => 3,
                        'piprop' => 'thumbnail',
                        'pithumbsize' => 300,
                        'titles' => $title,
                    ],
                    'timeout' => self::TIMEOUT,
                ]);

                $data = $response->toArray();

                if (isset($data['error'])) {
                    throw new Exception(sprintf('Page "%s" not found: %s', $title, $data['error']['info'] ?? 'Unknown error'));
                }

                if (!isset($data['query']['pages'])) {
                    throw new Exception(sprintf('No content found for page "%s"', $title));
                }

                $pages = $data['query']['pages'];
                $page = reset($pages); // Get the first (and only) page

                if (isset($page['missing'])) {
                    throw new Exception(sprintf('Page "%s" does not exist', $title));
                }

                $this->logger->info('Wikipedia extract fetched from API (cached)', ['title' => $title]);

                return [
                    'title' => $page['title'] ?? $title,
                    'extract' => $page['extract'] ?? '',
                    'thumbnail' => $page['thumbnail']['source'] ?? null,
                ];
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Wikipedia API transport error', [
                    'title' => $title,
                    'message' => $e->getMessage(),
                ]);
                throw new Exception(sprintf('Error fetching extract for "%s": %s', $title, $e->getMessage()), 0, $e);
            }
        });
    }

    /**
     * Search Wikipedia pages by title
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array{titles: array<string>}
     * @throws Exception if an error occurs
     */
    public function searchPages(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return ['titles' => []];
        }

        $cacheKey = $this->generateCacheKey('search', $query . '_' . $limit);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $limit) {
            $item->expiresAfter(3600); // Cache for 1 hour

            try {
                $response = $this->httpClient->request('GET', self::API_ENDPOINT, [
                    'query' => [
                        'action' => 'opensearch',
                        'search' => $query,
                        'limit' => $limit,
                        'namespace' => 0,
                        'format' => 'json',
                    ],
                    'timeout' => self::TIMEOUT,
                ]);

                $data = $response->toArray();

                if (isset($data[1]) && is_array($data[1])) {
                    $this->logger->info('Wikipedia search completed', ['query' => $query, 'results' => count($data[1])]);
                    return ['titles' => $data[1]];
                }

                return ['titles' => []];

            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Wikipedia search error', [
                    'query' => $query,
                    'message' => $e->getMessage(),
                ]);
                throw new Exception(sprintf('Error searching Wikipedia: %s', $e->getMessage()), 0, $e);
            }
        });
    }

    /**
     * Generate a cache key for Wikipedia content
     */
    private function generateCacheKey(string $type, string $title): string
    {
        // Normalize the title to avoid cache key issues
        $normalizedTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title);
        return sprintf('wikipedia_%s_%s', $type, $normalizedTitle);
    }
}
