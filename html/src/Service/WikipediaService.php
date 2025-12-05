<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class WikipediaService
{
    private const API_ENDPOINT = 'https://fr.wikipedia.org/w/api.php';
    private const TIMEOUT = 10;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get the content of a Wikipedia page
     *
     * @throws \Exception if the page does not exist or an error occurs
     */
    public function getPage(string $title): array
    {
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
                throw new \Exception(sprintf('Page "%s" not found: %s', $title, $data['error']['info'] ?? 'Unknown error'));
            }

            if (!isset($data['parse']['text'])) {
                throw new \Exception(sprintf('No content found for page "%s"', $title));
            }

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
            throw new \Exception(sprintf('Error fetching page "%s": %s', $title, $e->getMessage()), 0, $e);
        }
    }

    /**
     * Check if a Wikipedia page exists
     */
    public function pageExists(string $title): bool
    {
        try {
            $this->getPage($title);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get a short extract of a Wikipedia page for preview
     *
     * @return array{title: string, extract: string, thumbnail: string|null}
     * @throws \Exception if the page does not exist or an error occurs
     */
    public function getPageExtract(string $title): array
    {
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
                throw new \Exception(sprintf('Page "%s" not found: %s', $title, $data['error']['info'] ?? 'Unknown error'));
            }

            if (!isset($data['query']['pages'])) {
                throw new \Exception(sprintf('No content found for page "%s"', $title));
            }

            $pages = $data['query']['pages'];
            $page = reset($pages); // Get the first (and only) page

            if (isset($page['missing'])) {
                throw new \Exception(sprintf('Page "%s" does not exist', $title));
            }

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
            throw new \Exception(sprintf('Error fetching extract for "%s": %s', $title, $e->getMessage()), 0, $e);
        }
    }
}
