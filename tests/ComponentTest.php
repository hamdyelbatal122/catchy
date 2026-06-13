<?php

namespace Hamzi\Catchy\Tests;

use Illuminate\Support\Facades\Blade;

/**
 * Class ComponentTest
 *
 * Verifies that the Catchy package Blade UI components render correctly.
 *
 * @package Hamzi\Catchy\Tests
 */
class ComponentTest extends TestCase
{
    /**
     * Verify that the spinner component compiles and renders correct size and color.
     */
    public function test_spinner_component_renders(): void
    {
        $html = Blade::render('<x-catchy-spinner size="sm" color="accent" class="my-test" />');

        $this->assertStringContainsString('animate-spin', $html);
        $this->assertStringContainsString('h-4 w-4', $html);
        $this->assertStringContainsString('text-cyan-500', $html);
        $this->assertStringContainsString('my-test', $html);
    }

    /**
     * Verify that the skeleton component compiles and renders correct layouts.
     */
    public function test_skeleton_component_renders(): void
    {
        $html = Blade::render('<x-catchy-skeleton type="circle" class="my-skel" />');

        $this->assertStringContainsString('animate-pulse', $html);
        $this->assertStringContainsString('rounded-full', $html);
        $this->assertStringContainsString('my-skel', $html);
    }

    /**
     * Verify that the fade-in animation component compiles and outputs the correct attributes.
     */
    public function test_fade_component_renders(): void
    {
        $html = Blade::render('<x-catchy-fade duration="500">Content</x-catchy-fade>');

        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('duration-500', $html);
        $this->assertStringContainsString('Content', $html);
    }

    /**
     * Verify that the form component compiles and renders correct listeners, CSRF, and method fields.
     */
    public function test_form_component_renders(): void
    {
        $html = Blade::render('<x-catchy-form action="/submit" method="PUT" beforesend="onBefore()" success="onSuccess()" error="onError()">Input</x-catchy-form>');

        $this->assertStringContainsString('action="/submit"', $html);
        $this->assertStringContainsString('method="POST"', $html); // HTTP POST for spoofed PUT
        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('@catchy:start="onBefore()"', $html);
        $this->assertStringContainsString('@catchy:end="onSuccess()"', $html);
        $this->assertStringContainsString('@catchy:error="onError()"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('Input', $html);
    }

    /**
     * Verify that the catchy directive compiles correctly.
     */
    public function test_catchy_directive_renders(): void
    {
        $html = Blade::render('<form action="/submit" @catchyForm(["beforesend" => "onBefore", "success" => "onSuccess", "error" => "onError"])>Form</form>');

        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('@catchy:start="onBefore"', $html);
        $this->assertStringContainsString('data-catchy-beforesend="onBefore"', $html);
        $this->assertStringContainsString('@catchy:end="onSuccess"', $html);
        $this->assertStringContainsString('data-catchy-success="onSuccess"', $html);
        $this->assertStringContainsString('@catchy:error="onError"', $html);
        $this->assertStringContainsString('data-catchy-error="onError"', $html);
    }

    /**
     * Verify that the modal component compiles and renders correct structure and event listeners.
     */
    public function test_modal_component_renders(): void
    {
        $html = Blade::render('<x-catchy-modal id="my-test-modal" title="Hello Title">Modal Content</x-catchy-modal>');

        $this->assertStringContainsString('id="my-test-modal"', $html);
        $this->assertStringContainsString('catchy-modal', $html);
        $this->assertStringContainsString('Hello Title', $html);
        $this->assertStringContainsString('Modal Content', $html);
        $this->assertStringContainsString('@catchy:modal-load.window', $html);
        $this->assertStringContainsString('@catchy:modal-close.window', $html);
    }

    /**
     * Verify that the toast component compiles and renders correct structure and session/event handlers.
     */
    public function test_toast_component_renders(): void
    {
        $html = Blade::render('<x-catchy-toast position="bottom-right" duration="5000" />');

        $this->assertStringContainsString('@catchy:flash.window', $html);
        $this->assertStringContainsString('bottom-5 end-5', $html);
    }

    /**
     * Verify that the progress component compiles and renders correct structure.
     */
    public function test_progress_component_renders(): void
    {
        $html = Blade::render('<x-catchy-progress color="success" height="h-4" label="تنزيل الملفات" />');

        $this->assertStringContainsString('bg-emerald-500', $html);
        $this->assertStringContainsString('h-4', $html);
        $this->assertStringContainsString('تنزيل الملفات', $html);
        $this->assertStringContainsString('catchy-progress', $html);
    }

    /**
     * Verify that the upload component compiles and renders correct structure.
     */
    public function test_upload_component_renders(): void
    {
        $html = Blade::render('<x-catchy-upload name="avatar" label="حمل صورتك" accept="image/*" multiple />');

        $this->assertStringContainsString('name="avatar"', $html);
        $this->assertStringContainsString('accept="image/*"', $html);
        $this->assertStringContainsString('multiple', $html);
        $this->assertStringContainsString('حمل صورتك', $html);
    }

    /**
     * Verify that components render translation strings based on current locale.
     */
    public function test_components_use_translations_based_on_locale(): void
    {
        // 1. Test Arabic locale (should load Arabic text by default)
        $this->app->setLocale('ar');
        
        $progressHtml = Blade::render('<x-catchy-progress />');
        $this->assertStringContainsString('جاري تحميل الملفات...', $progressHtml);

        $uploadHtml = Blade::render('<x-catchy-upload name="doc" />');
        $this->assertStringContainsString('اسحب الملفات هنا أو انقر للاختيار', $uploadHtml);

        // 2. Test English locale (should load English text by default)
        $this->app->setLocale('en');

        $progressHtmlEn = Blade::render('<x-catchy-progress />');
        $this->assertStringContainsString('Loading files...', $progressHtmlEn);

        $uploadHtmlEn = Blade::render('<x-catchy-upload name="doc" />');
        $this->assertStringContainsString('Drag &amp; drop files here or click to browse', $uploadHtmlEn);
    }

    /**
     * Verify that CatchyDirective getJavaScript successfully caches the JS file contents in memory.
     */
    public function test_directive_caches_javascript_in_memory(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'catchy_test');
        file_put_contents($tempFile, 'console.log("Cached JS");');

        $content1 = \Hamzi\Catchy\CatchyDirective::getJavaScript($tempFile);
        $this->assertEquals('console.log("Cached JS");', $content1);

        // Modify the file on disk
        file_put_contents($tempFile, 'console.log("Modified JS");');

        // Reading again should return cached content
        $content2 = \Hamzi\Catchy\CatchyDirective::getJavaScript($tempFile);
        $this->assertEquals('console.log("Cached JS");', $content2);

        unlink($tempFile);
    }
}

