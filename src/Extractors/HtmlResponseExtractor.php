<?php

namespace Hamzi\Catchy\Extractors;

use Hamzi\Catchy\Contracts\ResponseExtractorInterface;

/**
 * Class HtmlResponseExtractor
 *
 * Implements ResponseExtractorInterface utilizing DOMDocument and DOMXPath to safely parse HTML
 * and extract layout components while preserving UTF-8 encodings.
 *
 * @package Hamzi\Catchy\Extractors
 */
class HtmlResponseExtractor implements ResponseExtractorInterface
{
    /**
     * Extract the outer HTML of the container matching containerId.
     *
     * @param  string  $html
     * @param  string  $containerId
     * @return string|null
     */
    public function extract(string $html, string $containerId): ?string
    {
        $dom = $this->parseHtml($html);
        if (!$dom) {
            return null;
        }

        return $this->extractContainerFromDom($dom, $containerId);
    }

    /**
     * Extract the title text content from the HTML page.
     *
     * @param  string  $html
     * @return string|null
     */
    public function extractTitle(string $html): ?string
    {
        $dom = $this->parseHtml($html);
        if (!$dom) {
            return null;
        }

        return $this->extractTitleFromDom($dom);
    }

    /**
     * Extract both title and container in a single DOM parse operation.
     *
     * @param  string  $html
     * @param  string  $containerId
     * @return array{title: string|null, fragment: string|null}
     */
    public function extractAll(string $html, string $containerId): array
    {
        $dom = $this->parseHtml($html);
        if (!$dom) {
            return ['title' => null, 'fragment' => null];
        }

        return [
            'title' => $this->extractTitleFromDom($dom),
            'fragment' => $this->extractContainerFromDom($dom, $containerId),
        ];
    }

    /**
     * Parse raw HTML into a DOMDocument with UTF-8 support.
     *
     * @param  string  $html
     * @return \DOMDocument|null
     */
    protected function parseHtml(string $html): ?\DOMDocument
    {
        if (empty($html)) {
            return null;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();

        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        return $loaded ? $dom : null;
    }

    /**
     * Extract the outer HTML of a container element by ID from a parsed DOM.
     *
     * @param  \DOMDocument  $dom
     * @param  string  $containerId
     * @return string|null
     */
    protected function extractContainerFromDom(\DOMDocument $dom, string $containerId): ?string
    {
        $xpath = new \DOMXPath($dom);

        // Safely escape the containerId to prevent XPath injection
        $escapedId = addcslashes($containerId, "'\\");
        $nodes = $xpath->query("//*[@id='{$escapedId}']");

        if ($nodes->length === 0) {
            return null;
        }

        $fragment = $dom->saveHTML($nodes->item(0));

        return $fragment ?: null;
    }

    /**
     * Extract the page title from a parsed DOM.
     *
     * @param  \DOMDocument  $dom
     * @return string|null
     */
    protected function extractTitleFromDom(\DOMDocument $dom): ?string
    {
        $xpath = new \DOMXPath($dom);
        $titleNode = $xpath->query('//title');

        if ($titleNode->length === 0) {
            return null;
        }

        $title = trim($titleNode->item(0)->textContent);

        return $title !== '' ? $title : null;
    }
}
