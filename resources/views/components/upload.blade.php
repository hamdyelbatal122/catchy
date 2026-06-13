@props([
    'name' => 'file',
    'multiple' => false,
    'accept' => '*/*',
    'label' => null,
    'helpText' => null,
])

@php
    $label = $label ?? __('catchy::messages.drag_drop_label');
    $helpText = $helpText ?? __('catchy::messages.help_text');
@endphp

<div 
    {{ $attributes->merge([
        'class' => 'w-full'
    ]) }}
    x-data="{
        dragover: false,
        files: [],
        updating: false,
        error: '',
        addFiles(fileList) {
            if (this.updating) return;
            this.error = '';
            const newFiles = Array.from(fileList).map(file => {
                if (file.type.startsWith('image/')) {
                    file.previewUrl = URL.createObjectURL(file);
                }
                return file;
            });
            if ({{ $multiple ? 'true' : 'false' }}) {
                this.files = [...this.files, ...newFiles];
            } else {
                this.files.forEach(file => {
                    if (file.previewUrl) {
                        URL.revokeObjectURL(file.previewUrl);
                    }
                });
                this.files = newFiles.slice(0, 1);
            }
            this.updateInput();
        },
        removeFile(index) {
            const file = this.files[index];
            if (file && file.previewUrl) {
                URL.revokeObjectURL(file.previewUrl);
            }
            this.files.splice(index, 1);
            this.updateInput();
        },
        updateInput() {
            this.updating = true;
            try {
                const dt = new DataTransfer();
                this.files.forEach(file => dt.items.add(file));
                this.$refs.fileInput.files = dt.files;
                
                // Trigger change event to notify form or other validation
                this.$refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            } finally {
                this.updating = false;
            }
        },
        getFileSize(size) {
            if (size < 1024) return size + ' B';
            if (size < 1048576) return (size / 1024).toFixed(1) + ' KB';
            return (size / 1048576).toFixed(1) + ' MB';
        },
        isImage(file) {
            return file.type.startsWith('image/');
        },
        getPreviewUrl(file) {
            return file.previewUrl || '';
        },
        handleValidationErrors(event) {
            const key = '{{ $name }}'.replace(/\[\]/g, '').replace(/\[/g, '.').replace(/\]/g, '');
            if (event.detail && event.detail[key]) {
                this.error = event.detail[key][0];
            }
        },
        destroy() {
            this.files.forEach(file => {
                if (file.previewUrl) {
                    URL.revokeObjectURL(file.previewUrl);
                }
            });
        }
    }"
    x-on:catchy-validation-errors.window="handleValidationErrors($event)"
    x-on:catchy:validation-errors.window="handleValidationErrors($event)"
>
    <!-- Hidden input -->
    <input 
        type="file" 
        name="{{ $name }}" 
        id="catchy-upload-{{ $name }}"
        x-ref="fileInput" 
        class="hidden" 
        accept="{{ $accept }}"
        @if($multiple) multiple @endif
        x-on:change="if (!updating) addFiles($event.target.files)"
    />

    <!-- Drag & Drop Container -->
    <div 
        x-on:dragover.prevent="dragover = true"
        x-on:dragleave.prevent="dragover = false"
        x-on:drop.prevent="dragover = false; addFiles($event.dataTransfer.files)"
        x-on:click="$refs.fileInput.click()"
        :class="{
            'border-indigo-500 bg-indigo-50/10 dark:bg-indigo-950/20 shadow-md scale-[1.01]': dragover,
            'border-gray-300 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 bg-gray-50/50 dark:bg-gray-900/50': !dragover
        }"
        class="relative flex flex-col items-center justify-center border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-300 ease-in-out group outline-none focus-within:ring-2 focus-within:ring-indigo-500"
        tabindex="0"
        x-on:keydown.enter="$refs.fileInput.click()"
        x-on:keydown.space="$refs.fileInput.click()"
    >
        <div class="mb-4 rounded-full bg-indigo-100/80 dark:bg-indigo-950/50 p-4 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
        </div>

        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            {{ $label }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ $helpText }}
        </p>
    </div>

    <!-- Preview Container -->
    <template x-if="files.length > 0">
        <div class="mt-4 space-y-2">
            <template x-for="(file, index) in files" :key="index">
                <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shadow-sm transition-all duration-200 hover:shadow">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Thumbnail Preview -->
                        <template x-if="isImage(file)">
                            <img :src="getPreviewUrl(file)" class="h-10 w-10 object-cover rounded-md border border-gray-100 dark:border-gray-800 flex-shrink-0" />
                        </template>
                        <template x-if="!isImage(file)">
                            <div class="h-10 w-10 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-900 border border-gray-100 dark:border-gray-850 flex-shrink-0 text-gray-400 dark:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </template>

                        <!-- File Info -->
                        <div class="min-w-0 flex-1 px-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate" x-text="file.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="getFileSize(file.size)"></p>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <button 
                        type="button" 
                        x-on:click.stop="removeFile(index)" 
                        class="p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                        title="{{ __('catchy::messages.delete_file') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </template>

    <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600 dark:text-red-400 font-semibold" style="display: none;"></p>
</div>
