<?php

namespace App\Service;

use DOMDocument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HtmlCleaner
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Clean and transform Wikipedia HTML content
     */
    public function clean(string $html, int $gameId): string
    {
        // Suppress DOMDocument warnings for malformed HTML
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        // Remove unwanted sections
        $this->removeUnwantedSections($dom);

        // Transform internal links
        $this->transformLinks($dom, $gameId);

        // Get the cleaned HTML
        $cleanedHtml = $dom->saveHTML();

        // Remove the XML encoding declaration we added
        $cleanedHtml = str_replace('<?xml encoding="UTF-8">', "", $cleanedHtml);

        return $cleanedHtml;
    }

    /**
     * Remove unwanted sections from Wikipedia content
     */
    private function removeUnwantedSections(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);

        // Selectors for sections to remove
        $selectorsToRemove = [
            '//*[@id="Références"]//ancestor::*[contains(@class, "mw-heading")]',
            '//*[@id="Références"]//following-sibling::*',
            '//*[@id="Notes_et_références"]//ancestor::*[contains(@class, "mw-heading")]',
            '//*[@id="Notes_et_références"]//following-sibling::*',
            '//*[@id="Liens_externes"]//ancestor::*[contains(@class, "mw-heading")]',
            '//*[@id="Liens_externes"]//following-sibling::*',
            '//*[@id="Voir_aussi"]//ancestor::*[contains(@class, "mw-heading")]',
            '//*[@id="Voir_aussi"]//following-sibling::*',
            '//*[contains(@class, "reference")]',
            '//*[contains(@class, "reflist")]',
            '//*[contains(@class, "navbox")]',
            '//*[@role="navigation"]',
            '//sup[contains(@class, "reference")]',
            // remove mw-editsection
            '//*[contains(@class, "mw-editsection")]',
        ];

        foreach ($selectorsToRemove as $selector) {
            $nodes = $xpath->query($selector);
            foreach ($nodes as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    /**
     * Transform internal Wikipedia links for Turbo navigation
     */
    private function transformLinks(\DOMDocument $dom, int $gameId): void
    {
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//a[@href]');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            // Only process internal Wikipedia links
            if (str_starts_with($href, '/wiki/')) {
                // Extract page title from URL
                $pageTitle = urldecode(substr($href, 6));

                // Skip special pages (Help:, Wikipedia:, File:, etc.)
                if (preg_match('/^([A-Z][a-z]+:|Special:)/i', $pageTitle)) {
                    continue;
                }

                // Remove fragment (anchor)
                $pageTitle = explode('#', $pageTitle)[0];

                // Generate the game route URL
                $newHref = $this->urlGenerator->generate('game_load_page', [
                    'id' => $gameId,
                    'title' => $pageTitle,
                ]);

                // Update the link
                $link->setAttribute('href', $newHref);
                $link->setAttribute('data-turbo-frame', 'wiki-content');

            } else {
                // For external links or other links, disable Turbo
                $link->setAttribute('data-turbo', 'false');
                $link->setAttribute('target', '_blank');
                $link->setAttribute('rel', 'noopener noreferrer');
            }
        }

    }
}
