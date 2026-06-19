<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Tests;

use Hamzi\Catchy\Infrastructure\Extractors\HtmlResponseExtractor;
use Hamzi\Catchy\Support\FlashExtractor;
use Illuminate\Http\Request;

/**
 * Class ExtractorAndSafetyTest
 *
 * Verifies HtmlResponseExtractor XPath injection protection, DOM extraction correctness,
 * and FlashExtractor session processing.
 *
 * @package Hamzi\Catchy\Tests
 */
class ExtractorAndSafetyTest extends TestCase
{
    /**
     * Test HtmlResponseExtractor behaves correctly when extracting titles and heads.
     */
    public function test_extractor_can_extract_head_title_and_fragment(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test Title</title><link rel="stylesheet" href="style.css"></head><body><div id="catchy-app"><h1>Hello Catchy</h1></div></body></html>';
        $extractor = new HtmlResponseExtractor();

        $result = $extractor->extractAll($html, 'catchy-app');

        $this->assertEquals('Test Title', $result['title']);
        $this->assertStringContainsString('style.css', $result['head']);
        $this->assertStringNotContainsString('title', $result['head']); // title should be excluded
        $this->assertStringContainsString('<h1>Hello Catchy</h1>', $result['fragment']);
    }

    /**
     * Test HtmlResponseExtractor XPath escaping protects against single and double quotes.
     */
    public function test_extractor_escapes_xpath_container_ids(): void
    {
        $extractor = new HtmlResponseExtractor();

        // 1. Double quotes in ID
        $html1 = '<html><body><div id="foo&quot;bar">Double Quote Content</div></body></html>';
        $result1 = $extractor->extract($html1, 'foo"bar');
        $this->assertStringContainsString('Double Quote Content', $result1);

        // 2. Single quotes in ID
        $html2 = '<html><body><div id="foo\'bar">Single Quote Content</div></body></html>';
        $result2 = $extractor->extract($html2, "foo'bar");
        $this->assertStringContainsString('Single Quote Content', $result2);

        // 3. Both quotes in ID
        $html3 = '<html><body><div id="foo\'&quot;bar">Mixed Content</div></body></html>';
        $result3 = $extractor->extract($html3, "foo'\"bar");
        $this->assertStringContainsString('Mixed Content', $result3);
    }

    /**
     * Test HtmlResponseExtractor preserves the global libxml_use_internal_errors state.
     */
    public function test_extractor_preserves_libxml_errors_state(): void
    {
        $extractor = new HtmlResponseExtractor();

        // Set state to false
        libxml_use_internal_errors(false);
        $extractor->extract('<html><body><div>Test</div></body></html>', 'app');
        $this->assertFalse(libxml_use_internal_errors());

        // Set state to true
        libxml_use_internal_errors(true);
        $extractor->extract('<html><body><div>Test</div></body></html>', 'app');
        $this->assertTrue(libxml_use_internal_errors());

        // Reset to default false
        libxml_use_internal_errors(false);
    }

    /**
     * Test FlashExtractor logic (clear vs read-only).
     */
    public function test_flash_extractor_reads_and_clears(): void
    {
        $session = $this->createMock(\Illuminate\Session\Store::class);
        $session->expects($this->any())->method('has')->willReturnMap([
            ['success', true],
            ['error', true],
            ['warning', false],
            ['info', false],
            ['status', false],
            ['errors', false]
        ]);

        // Expect get to be called in read-only mode
        $session->expects($this->any())->method('get')->willReturnMap([
            ['success', 'Success message'],
            ['error', 'Error message']
        ]);

        // Expect pull to be called in clear mode
        $session->expects($this->any())->method('pull')->willReturnMap([
            ['success', 'Success message cleared'],
            ['error', 'Error message cleared']
        ]);

        $request = new Request();
        $request->setLaravelSession($session);

        // 1. Test read-only mode
        $flash = FlashExtractor::extract($request, false);
        $this->assertEquals('Success message', $flash['success']);
        $this->assertEquals('Error message', $flash['error']);

        // 2. Test clear mode
        $flashCleared = FlashExtractor::extract($request, true);
        $this->assertEquals('Success message cleared', $flashCleared['success']);
        $this->assertEquals('Error message cleared', $flashCleared['error']);
    }
}
