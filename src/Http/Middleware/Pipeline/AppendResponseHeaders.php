<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Http\Middleware\Pipeline;

use Closure;
use Hamzi\Catchy\Domain\Contracts\PipelineStageInterface;
use Hamzi\Catchy\Domain\Contracts\VersionRepositoryInterface;
use Hamzi\Catchy\Domain\ValueObjects\CatchyPipelineData;
use Hamzi\Catchy\Support\FlashExtractor;

/**
 * Class AppendResponseHeaders
 *
 * Pipeline stage adding asset versions and session flash messages (encoded as base64 JSON)
 * directly to the HTTP response headers for processing in the frontend SPA router.
 */
class AppendResponseHeaders implements PipelineStageInterface
{
    /**
     * The asset version repository instance.
     */
    protected VersionRepositoryInterface $versionRepository;

    /**
     * AppendResponseHeaders constructor.
     */
    public function __construct(VersionRepositoryInterface $versionRepository)
    {
        $this->versionRepository = $versionRepository;
    }

    /**
     * Handle the pipeline stage.
     *
     * @param  Closure(CatchyPipelineData): (CatchyPipelineData)  $next
     */
    public function handle(CatchyPipelineData $data, Closure $next): CatchyPipelineData
    {
        $response = clone $data->getResponse();
        $request = $data->getRequest();

        $serverVersion = $this->versionRepository->getVersion();

        // 1. Append the current version header to the response
        if ($serverVersion !== '') {
            $response->headers->set('X-Catchy-Version', $serverVersion);
        }

        // 2. Append flash messages from session to header if session exists
        $flash = FlashExtractor::extract($request, true);

        if (! empty($flash)) {
            $response->headers->set('X-Catchy-Flash', base64_encode((string) json_encode($flash)));
        }

        return $next($data->withResponse($response));
    }
}
