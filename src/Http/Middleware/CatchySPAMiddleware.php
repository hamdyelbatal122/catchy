<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Http\Middleware;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response;
use Hamzi\Catchy\Domain\ValueObjects\CatchyPipelineData;

/**
 * Class CatchySPAMiddleware
 *
 * Coordinates SPA request filtering by sending incoming request-response flows
 * through a configurable pipeline of clean architecture stages.
 *
 * @package Hamzi\Catchy\Http\Middleware
 */
class CatchySPAMiddleware
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected Container $container;

    /**
     * CatchySPAMiddleware constructor.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Detect if this is an SPA request from Catchy
        if (!$request->headers->has('X-Catchy-SPA')) {
            return $next($request);
        }

        // 2. Skip middleware if the route is explicitly excluded
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        // 3. Process request to get the initial response
        $response = $next($request);

        // 4. Wrap request and response in value object
        $pipelineData = new CatchyPipelineData($request, $response);

        // 5. Resolve the configured pipeline stages
        $stages = config('catchy.pipeline', []);

        /** @var CatchyPipelineData $processed */
        $processed = (new Pipeline($this->container))
            ->send($pipelineData)
            ->through($stages)
            ->then(fn (CatchyPipelineData $data) => $data);

        return $processed->getResponse();
    }

    /**
     * Determine if the request matches any of the configured exclusion patterns.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldExclude(Request $request): bool
    {
        $excepts = config('catchy.except', []);

        foreach ($excepts as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
