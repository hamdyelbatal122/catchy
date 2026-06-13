<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Hamzi\Catchy\Contracts\ResponseExtractorInterface;
use Hamzi\Catchy\Contracts\VersionProviderInterface;

/**
 * Class CatchySPAMiddleware
 *
 * Coordinates SPA request filtering, asset version mismatch handling, and response
 * extraction using decoupled interface contracts.
 *
 * @package Hamzi\Catchy\Http\Middleware
 */
class CatchySPAMiddleware
{
    /**
     * The response extractor instance.
     *
     * @var \Hamzi\Catchy\Contracts\ResponseExtractorInterface
     */
    protected ResponseExtractorInterface $extractor;

    /**
     * The asset version provider instance.
     *
     * @var \Hamzi\Catchy\Contracts\VersionProviderInterface
     */
    protected VersionProviderInterface $versionProvider;

    /**
     * CatchySPAMiddleware constructor.
     *
     * @param  \Hamzi\Catchy\Contracts\ResponseExtractorInterface  $extractor
     * @param  \Hamzi\Catchy\Contracts\VersionProviderInterface  $versionProvider
     */
    public function __construct(ResponseExtractorInterface $extractor, VersionProviderInterface $versionProvider)
    {
        $this->extractor = $extractor;
        $this->versionProvider = $versionProvider;
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

        // 2. Asset version verification (Inertia-style)
        $serverVersion = $this->versionProvider->getVersion();
        
        if ($serverVersion !== '') {
            $clientVersion = $request->header('X-Catchy-Version', '');
            
            // If the client has a version cached, and it differs from the server's build version
            if ($clientVersion !== '' && $clientVersion !== $serverVersion) {
                // Return a 409 Conflict response to trigger a hard client-side reload
                return response('', 409, [
                    'X-Catchy-Version' => $serverVersion,
                ]);
            }
        }

        // 3. Process the request to get the response
        $response = $next($request);

        // Intercept redirects and convert them to 200 OK with X-Catchy-Redirect header to preserve SPA routing and headers
        if ($response->isRedirection()) {
            $redirectUrl = $response->headers->get('Location');
            $flash = [];
            if ($request->hasSession()) {
                foreach (['success', 'error', 'warning', 'info', 'status'] as $key) {
                    if ($request->session()->has($key)) {
                        $flash[$key] = $request->session()->pull($key);
                    }
                }

                // Extract validation errors from session
                if ($request->session()->has('errors')) {
                    $errorBag = $request->session()->get('errors');
                    if (method_exists($errorBag, 'getBag')) {
                        $flash['validation_errors'] = $errorBag->getBag('default')->toArray();
                    } elseif (method_exists($errorBag, 'toArray')) {
                        $flash['validation_errors'] = $errorBag->toArray();
                    }
                }
            }

            $headers = [
                'X-Catchy-Redirect' => $redirectUrl,
                'X-Catchy-SPA' => 'true',
            ];

            if (!empty($flash)) {
                $headers['X-Catchy-Flash'] = base64_encode(json_encode($flash));
            }

            if ($serverVersion !== '') {
                $headers['X-Catchy-Version'] = $serverVersion;
            }

            return response('', 200, $headers);
        }

        // 4. Append the current version header to the response
        if ($serverVersion !== '') {
            $response->headers->set('X-Catchy-Version', $serverVersion);
        }

        // 5. Append flash messages from session to header if session exists
        if ($request->hasSession()) {
            $flash = [];
            foreach (['success', 'error', 'warning', 'info', 'status'] as $key) {
                if ($request->session()->has($key)) {
                    $flash[$key] = $request->session()->pull($key);
                }
            }

            // Extract validation errors from session
            if ($request->session()->has('errors')) {
                $errorBag = $request->session()->get('errors');
                if (method_exists($errorBag, 'getBag')) {
                    $flash['validation_errors'] = $errorBag->getBag('default')->toArray();
                } elseif (method_exists($errorBag, 'toArray')) {
                    $flash['validation_errors'] = $errorBag->toArray();
                }
            }

            if (!empty($flash)) {
                $response->headers->set('X-Catchy-Flash', base64_encode(json_encode($flash)));
            }
        }

        // 6. Only intercept successful, non-redirection HTML responses
        if ($this->shouldIntercept($response)) {
            $this->interceptResponse($response);
        }

        return $response;
    }

    /**
     * Determine if the response should be intercepted and trimmed.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function shouldIntercept(Response $response): bool
    {
        // Must be status 200 OK
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Must be a standard HTML response
        $contentType = $response->headers->get('Content-Type');
        if (!$contentType || !str_contains($contentType, 'text/html')) {
            return false;
        }

        return true;
    }

    /**
     * Intercept the response and extract only the target app container.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function interceptResponse(Response $response): void
    {
        $content = $response->getContent();
        if (empty($content)) {
            return;
        }

        $containerId = config('catchy.container_id', 'catchy-app');

        // Extract title and container in a single DOM parse operation
        $result = $this->extractor->extractAll($content, $containerId);

        if ($result['title'] !== null) {
            $response->headers->set('X-Catchy-Title', base64_encode($result['title']));
        }

        if ($result['fragment'] !== null) {
            $response->setContent($result['fragment']);
        }
    }
}
