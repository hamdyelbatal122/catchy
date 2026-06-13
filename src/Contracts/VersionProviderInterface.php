<?php

namespace Hamzi\Catchy\Contracts;

/**
 * Interface VersionProviderInterface
 *
 * Defines the contract for resolving the current assets version.
 *
 * @package Hamzi\Catchy\Contracts
 */
interface VersionProviderInterface
{
    /**
     * Get the current version of the application assets.
     *
     * @return string
     */
    public function getVersion(): string;
}
