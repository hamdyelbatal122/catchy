<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Http\Middleware\Pipeline;

use Closure;
use Hamzi\Catchy\Domain\Contracts\PipelineStageInterface;
use Hamzi\Catchy\Domain\Contracts\VersionRepositoryInterface;
use Hamzi\Catchy\Domain\ValueObjects\CatchyPipelineData;
use Hamzi\Catchy\Support\FlashExtractor;

/**
 * Class HandleRedirectResponse
 *
 * Pipeline stage intercepting redirect responses and rewriting them to
 * standard 200 OK headers for client-side SPA routing redirection.
 */
class HandleRedirectResponse implements PipelineStageInterface
{
    /**
     * The asset version repository instance.
     */
    protected VersionRepositoryInterface $versionRepository;

    /**
     * HandleRedirectResponse constructor.
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
        $response = $data->getResponse();

        if ($response->isRedirection()) {
            $request = $data->getRequest();
            $serverVersion = $this->versionRepository->getVersion();

            $headers = [
                'X-Catchy-Redirect' => $response->headers->get('Location'),
                'X-Catchy-SPA' => 'true',
            ];

            $flash = FlashExtractor::extract($request, false);
            if (! empty($flash)) {
                $headers['X-Catchy-Flash'] = base64_encode((string) json_encode($flash));
            }

            if ($serverVersion !== '') {
                $headers['X-Catchy-Version'] = $serverVersion;
            }

            $redirectResponse = response('', 200, $headers);

            return $data->withResponse($redirectResponse);
        }

        return $next($data);
    }
}
