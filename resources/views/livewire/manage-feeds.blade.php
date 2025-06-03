<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manage Feeds') }}
        </h2>
    </x-slot>

    <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <div class="flex space-x-4">
                <x-success-button wire:click="create">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    {{ __('Add New Feed') }}
                </x-success-button>

                <x-primary-button x-data="{}" x-on:click="$dispatch('open-modal', 'import-opml-modal')">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    {{ __('Import OPML') }}
                </x-primary-button>

                <x-primary-button wire:click="exportOpml">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    {{ __('Export OPML') }}
                </x-primary-button>
            </div>

            <x-secondary-button type="link" href="{{ route('dashboard') }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                {{ __('Back to Dashboard') }}
            </x-secondary-button>
        </div>

        @if (session()->has('message'))
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ $editingFeedId ? __('Edit Feed') : __('Add New Feed') }}
                    </h2>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <x-input-label for="url" :value="__('RSS Feed URL')" />
                            <x-text-input wire:model="form.url" id="url" name="url" type="text" required autocomplete="url" />
                            <x-input-error class="mt-2" :messages="$errors->get('form.url')" />
                        </div>

                        <div>
                            <x-input-label for="title" :value="__('Title (optional)')" />
                            <x-text-input wire:model="form.title" id="title" name="title" type="text" autofocus autocomplete="title" />
                            <x-input-error class="mt-2" :messages="$errors->get('form.title')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description (optional)')" />
                            <x-textarea wire:model="form.description" id="description" name="description" type="text" autocomplete="description" />
                            <x-input-error class="mt-2" :messages="$errors->get('form.description')" />
                        </div>

                        <div class="flex justify-end">
                            @if($editingFeedId)
                                <x-secondary-button wire:click="create" class="mr-2">{{ __('Cancel') }}</x-secondary-button>
                            @endif

                            <x-primary-button type="submit">
                                {{ $editingFeedId ? __('Update Feed') : __('Add Feed') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Your Feeds') }}</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($feeds) }} {{ Str::plural('feed', count($feeds)) }}</span>
                    </div>
                    
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($feeds as $feed)
                            <li class="p-6 flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $feed->title }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $feed->url }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="javascript:;" wire:click="edit('{{ $feed->uuid }}')" class="text-blue-500 hover:text-blue-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 0L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <a href="javascript:;" wire:click="delete('{{ $feed->uuid }}')" wire:confirm="Are you sure you want to delete this feed?" class="text-red-500 hover:text-red-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                        @empty
                            <li class="p-6 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No feeds added yet. Add your first feed using the form or import from an OPML file.') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <x-modal name="import-opml-modal" :show="false" focusable>
        <form wire:submit="importOpml" class="p-6" enctype="multipart/form-data">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Import OPML File') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Upload an OPML file to import multiple RSS feeds at once. OPML is a standard format used to exchange lists of RSS feeds between different feed readers.') }}
            </p>

            <div class="mt-6">
                <label for="opmlFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('OPML File') }}</label>
                <input 
                    wire:model="opmlFile" 
                    type="file" 
                    id="opmlFile" 
                    accept=".opml,.xml"
                    class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                           file:mr-4 file:py-2 file:px-4
                           file:rounded file:border-0
                           file:text-sm file:font-semibold
                           file:bg-blue-50 file:text-blue-700
                           hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-200"
                >
                @error('opmlFile') <span class="mt-2 text-red-600 text-sm">{{ $message }}</span> @enderror
                
                <div wire:loading wire:target="opmlFile" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Uploading...') }}
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3" wire:loading.attr="disabled">
                    {{ __('Import Feeds') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
