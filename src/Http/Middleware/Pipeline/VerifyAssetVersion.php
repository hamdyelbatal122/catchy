<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Http\Middleware\Pipeline;

use Closure;
use Hamzi\Catchy\Domain\Contracts\PipelineStageInterface;
use Hamzi\Catchy\Domain\Contracts\VersionRepositoryInterface;
use Hamzi\Catchy\Domain\ValueObjects\CatchyPipelineData;

/**
 * Class VerifyAssetVersion
 *
 * Pipeline stage verifying client asset build versions against the server.
 * Returns a 409 Conflict if they mismatch to force a clean reload on the client.
 */
class VerifyAssetVersion implements PipelineStageInterface
{
    /**
     * The asset version repository instance.
     */
    protected VersionRepositoryInterface $versionRepository;

    /**
     * VerifyAssetVersion constructor.
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
        $request = $data->getRequest();
        $serverVersion = $this->versionRepository->getVersion();

        if ($serverVersion !== '') {
            $clientVersion = $request->header('X-Catchy-Version', '');

            // If client has a version, and it differs from the server's build version
            if ($clientVersion !== '' && $clientVersion !== $serverVersion) {
                // Terminate pipeline and return 409 response
                $response = response('', 409, [
                    'X-Catchy-Version' => $serverVersion,
                ]);

                return $data->withResponse($response);
            }
        }

        return $next($data);
    }
}
