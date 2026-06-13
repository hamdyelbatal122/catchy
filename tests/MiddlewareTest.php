<?php

namespace Hamzi\Catchy\Tests;

use Illuminate\Support\Facades\Route;
use Hamzi\Catchy\Http\Middleware\CatchySPAMiddleware;
use Hamzi\Catchy\Contracts\ResponseExtractorInterface;
use Hamzi\Catchy\Contracts\VersionProviderInterface;

/**
 * Class MiddlewareTest
 *
 * Tests the CatchySPAMiddleware behaviour including HTML extraction, status codes,
 * and asset version validation checks.
 *
 * @package Hamzi\Catchy\Tests
 */
class MiddlewareTest extends TestCase
{
    /**
     * Set up tests, registering test routes with the CatchySPAMiddleware applied.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(CatchySPAMiddleware::class)->group(function () {
            Route::get('/html-page', function () {
                return '<!DOCTYPE html><html><head><title>My Catchy Page 🚀</title></head><body><header>Nav</header><main id="catchy-app" class="p-4"><h1>Hello World</h1><p>Test</p></main><footer>Footer</footer></body></html>';
            });

            Route::get('/json-response', function () {
                return response()->json(['message' => 'Hello World']);
            });

            Route::get('/error-page', function () {
                return response('Error happened', 500);
            });

            Route::get('/no-container', function () {
                return '<!DOCTYPE html><html><head><title>No Container Page</title></head><body><p>Hello World without container</p></body></html>';
            });
        });
    }

    /**
     * Verify that standard requests (without X-Catchy-SPA header) receive full HTML.
     */
    public function test_normal_request_returns_full_html(): void
    {
        $response = $this->get('/html-page');

        $response->assertStatus(200);
        $response->assertSee('<!DOCTYPE html>', false);
        $response->assertSee('<header>Nav</header>', false);
        $response->assertSee('<footer>Footer</footer>', false);
        $response->assertSee('<h1>Hello World</h1>', false);
        $this->assertFalse($response->headers->has('X-Catchy-Title'));
    }

    /**
     * Verify that SPA requests (with X-Catchy-SPA header) receive only the outer HTML of the container.
     */
    public function test_spa_request_returns_only_container_and_title_header(): void
    {
        $response = $this->get('/html-page', [
            'X-Catchy-SPA' => 'true'
        ]);

        $response->assertStatus(200);
        $response->assertDontSee('<!DOCTYPE html>');
        $response->assertDontSee('<header>Nav</header>');
        $response->assertDontSee('<footer>Footer</footer>');
        
        // Assert outer HTML of the container is preserved
        $response->assertSee('<main id="catchy-app" class="p-4">', false);
        $response->assertSee('<h1>Hello World</h1>', false);
        $response->assertSee('</main>', false);

        // Assert title header is present and base64 encoded
        $this->assertTrue($response->headers->has('X-Catchy-Title'));
        $this->assertEquals(base64_encode('My Catchy Page 🚀'), $response->headers->get('X-Catchy-Title'));
    }

    /**
     * Verify that JSON responses are not intercepted.
     */
    public function test_json_responses_are_not_intercepted(): void
    {
        $response = $this->get('/json-response', [
            'X-Catchy-SPA' => 'true'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Hello World']);
        $this->assertFalse($response->headers->has('X-Catchy-Title'));
    }

    /**
     * Verify that non-200 responses are not intercepted.
     */
    public function test_non_200_responses_are_not_intercepted(): void
    {
        $response = $this->get('/error-page', [
            'X-Catchy-SPA' => 'true'
        ]);

        $response->assertStatus(500);
        $response->assertSee('Error happened');
        $this->assertFalse($response->headers->has('X-Catchy-Title'));
    }

    /**
     * Verify that when the container is missing from response HTML, it falls back to returning the full layout
     * but still extracts the page title if present.
     */
    public function test_missing_container_fallback_returns_full_page(): void
    {
        $response = $this->get('/no-container', [
            'X-Catchy-SPA' => 'true'
        ]);

        $response->assertStatus(200);
        $response->assertSee('<!DOCTYPE html>', false);
        $response->assertSee('No Container Page');
        $this->assertTrue($response->headers->has('X-Catchy-Title'));
        $this->assertEquals(base64_encode('No Container Page'), $response->headers->get('X-Catchy-Title'));
    }

    /**
     * Verify that version matches do not trigger conflicts and append current version header.
     */
    public function test_version_match_returns_200_with_version_header(): void
    {
        config(['catchy.version' => '1.0.0']);

        $response = $this->get('/html-page', [
            'X-Catchy-SPA' => 'true',
            'X-Catchy-Version' => '1.0.0'
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-Catchy-Version'));
        $this->assertEquals('1.0.0', $response->headers->get('X-Catchy-Version'));
    }

    /**
     * Verify that a mismatch in version returns 409 Conflict with the new version header.
     */
    public function test_version_mismatch_returns_409_conflict(): void
    {
        config(['catchy.version' => '2.0.0']);

        $response = $this->get('/html-page', [
            'X-Catchy-SPA' => 'true',
            'X-Catchy-Version' => '1.0.0' // Outdated version from client
        ]);

        $response->assertStatus(409);
        $this->assertTrue($response->headers->has('X-Catchy-Version'));
        $this->assertEquals('2.0.0', $response->headers->get('X-Catchy-Version'));
        $this->assertEmpty($response->getContent());
    }

    /**
     * Verify dependency injection contracts resolution.
     */
    public function test_contracts_are_resolvable_from_container(): void
    {
        $extractor = $this->app->make(ResponseExtractorInterface::class);
        $versionProvider = $this->app->make(VersionProviderInterface::class);

        $this->assertInstanceOf(\Hamzi\Catchy\Extractors\HtmlResponseExtractor::class, $extractor);
        $this->assertInstanceOf(\Hamzi\Catchy\Providers\AssetVersionProvider::class, $versionProvider);
    }

    /**
     * Verify that when session flash messages are present, they are injected into X-Catchy-Flash header.
     */
    public function test_session_flash_messages_are_injected_in_header(): void
    {
        Route::middleware([\Illuminate\Session\Middleware\StartSession::class, CatchySPAMiddleware::class])->get('/flash-route', function () {
            session()->flash('success', 'Operation completed successfully!');
            session()->flash('error', 'Something went wrong!');
            return 'OK';
        });

        $response = $this->get('/flash-route', [
            'X-Catchy-SPA' => 'true'
        ]);

        $this->assertTrue($response->headers->has('X-Catchy-Flash'));
        $flash = json_decode(base64_decode($response->headers->get('X-Catchy-Flash')), true);
        $this->assertEquals('Operation completed successfully!', $flash['success']);
        $this->assertEquals('Something went wrong!', $flash['error']);
    }
}
