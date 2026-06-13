<?php

namespace Hamzi\Catchy;

/**
 * Class CatchyDirective
 *
 * Compiles and renders the dynamic @catchy Blade directive attributes for forms.
 *
 * @package Hamzi\Catchy
 */
class CatchyDirective
{
    /**
     * Render the directive attributes dynamically.
     *
     * @param  array  $options
     * @return string
     */
    public static function render(array $options = []): string
    {
        $attributes = ['x-data'];

        if (isset($options['beforesend'])) {
            $attributes[] = '@catchy:start="' . e($options['beforesend']) . '"';
            $attributes[] = 'data-catchy-beforesend="' . e($options['beforesend']) . '"';
        }
        
        if (isset($options['success'])) {
            $attributes[] = '@catchy:end="' . e($options['success']) . '"';
            $attributes[] = 'data-catchy-success="' . e($options['success']) . '"';
        }
        
        if (isset($options['error'])) {
            $attributes[] = '@catchy:error="' . e($options['error']) . '"';
            $attributes[] = 'data-catchy-error="' . e($options['error']) . '"';
        }

        return implode(' ', $attributes);
    }
}
