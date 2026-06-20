<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Domain\Contracts;

use Closure;
use Hamzi\Catchy\Domain\ValueObjects\CatchyPipelineData;

/**
 * Interface PipelineStageInterface
 *
 * Defines the contract for all stages processed within the Catchy SPA HTTP pipeline.
 */
interface PipelineStageInterface
{
    /**
     * Handle the pipeline stage.
     *
     * @param  CatchyPipelineData  $data
     * @param  Closure(CatchyPipelineData): (CatchyPipelineData)  $next
     * @return CatchyPipelineData
     */
    public function handle(CatchyPipelineData $data, Closure $next): CatchyPipelineData;
}
