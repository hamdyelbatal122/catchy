@props([
    'field',
])

<div 
    x-data="{ 
        error: '',
        normalizeKey(key) {
            return key.replace(/\[\]/g, '').replace(/\[/g, '.').replace(/\]/g, '');
        },
        handleErrors(errors) {
            const target = this.normalizeKey('{{ $field }}');
            if (errors && errors[target]) {
                this.error = Array.isArray(errors[target]) ? errors[target][0] : errors[target];
            } else {
                this.error = '';
            }
        }
    }"
    x-on:catchy-validation-errors.window="handleErrors($event.detail)"
    x-on:catchy:validation-errors.window="handleErrors($event.detail)"
    x-show="error"
    {{ $attributes->merge(['class' => 'text-sm text-red-600 dark:text-red-400 mt-1 font-medium']) }}
    style="display: none;"
    x-text="error"
></div>
