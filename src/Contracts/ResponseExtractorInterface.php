<?php

namespace Hamzi\Catchy\Contracts;

/**
 * Interface ResponseExtractorInterface
 *
 * Defines the contract for parsing response HTML and extracting required fragments.
 *
 * @package Hamzi\Catchy\Contracts
 */
interface ResponseExtractorInterface
{
    /**
     * Extract the outer HTML of the container matching containerId.
     *
     * @param  string  $html
     * @param  string  $containerId
     * @return string|null
     */
    public function extract(string $html, string $containerId): ?string;

    /**
     * Extract the title text content from the HTML page.
     *
     * @param  string  $html
     * @return string|null
     */
    public function extractTitle(string $html): ?string;

    /**
     * Extract both title and container in a single DOM parse operation.
     *
     * @param  string  $html
     * @param  string  $containerId
     * @return array{title: string|null, fragment: string|null}
     */
    public function extractAll(string $html, string $containerId): array;
}
