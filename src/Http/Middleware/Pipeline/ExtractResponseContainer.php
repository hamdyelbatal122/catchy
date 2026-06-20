<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Http\Middleware\Pipeline;

use Closure;
use Hamzi\Catchy\Domain\Contracts\PipelineStageInterface;
use Hamzi\Catchy\Domain\Contracts\ResponseExtractorInterface;
use Hamzi\Catchy\Domain\ValueObjects\CatchyPipelineData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExtractResponseContainer
 *
 * Pipeline stage intercepting successful HTML responses to extract only the target SPA container,
 * page title, and head updates, updating the response body and appending headers.
 */
class ExtractResponseContainer implements PipelineStageInterface
{
    /**
     * The response extractor instance.
     */
    protected ResponseExtractorInterface $extractor;

    /**
     * ExtractResponseContainer constructor.
     */
    public function __construct(ResponseExtractorInterface $extractor)
    {
        $this->extractor = $extractor;
    }

    /**
     * Handle the pipeline stage.
     *
     * @param  Closure(CatchyPipelineData): (CatchyPipelineData)  $next
     */
    public function handle(CatchyPipelineData $data, Closure $next): CatchyPipelineData
    {
        $response = $data->getResponse();

        if ($this->shouldIntercept($response)) {
            $content = $response->getContent();

            if (is_string($content) && $content !== '') {
                $request = $data->getRequest();
                $containerId = $request->header('X-Catchy-Target', config('catchy.container_id', 'catchy-app'));
                if (str_starts_with($containerId, '#')) {
                    $containerId = substr($containerId, 1);
                }

                // Extract title, head, and container in a single DOM parse operation
                $result = $this->extractor->extractAll($content, $containerId);

                // Clone response to preserve immutability
                $response = clone $response;

                if ($result['title'] !== null) {
                    $response->headers->set('X-Catchy-Title', base64_encode($result['title']));
                }

                if ($result['head'] !== null) {
                    $response->headers->set('X-Catchy-Head', base64_encode($result['head']));
                }

                if ($result['fragment'] !== null) {
                    $response->setContent($result['fragment']);
                }

                return $next($data->withResponse($response));
            }
        }

        return $next($data);
    }

    /**
     * Determine if the response should be intercepted and trimmed.
     *
     * @param  Response  $response
     */
    protected function shouldIntercept($response): bool
    {
        // Must be status 200 OK
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Must be a standard HTML response
        $contentType = $response->headers->get('Content-Type');
        if (! $contentType || ! str_contains($contentType, 'text/html')) {
            return false;
        }

        return true;
    }
}
