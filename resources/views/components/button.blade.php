@props([
    'type' => 'button',
    'color' => 'secondary',
    'loadingTarget' => null,
    'loadingText' => null,
])

@php
    $baseClasses = 'inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none transition';

    $colorClasses = match($color) {
        'primary' => 'bg-blue-600 border border-transparent text-white hover:bg-blue-500 focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-700',
        'danger' => 'bg-red-600 border border-transparent text-white hover:bg-red-500 focus:border-red-700 focus:ring focus:ring-red-200 active:bg-red-700',
        'success' => 'bg-green-600 border border-transparent text-white hover:bg-green-500 focus:border-green-700 focus:ring focus:ring-green-200 active:bg-green-700',
        'secondary' => 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 ease-in-out duration-150',
        default => 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 ease-in-out duration-150',
    };

    $classes = $baseClasses . ' ' . $colorClasses;
@endphp

@if ($type === 'link')
    <a {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loadingTarget)
            <span wire:loading.remove wire:target="{{ $loadingTarget }}">{{ $slot }}</span>
            <span wire:loading wire:target="{{ $loadingTarget }}" class="flex items-center">
                <svg class="animate-spin inline-block -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $loadingText ?? __('Loading...') }}
            </span>
        @else
            {{ $slot }}
        @endif
    </a>
@else
    <button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }} @if($loadingTarget) wire:loading.attr="disabled" wire:target="{{ $loadingTarget }}" @endif>
        @if ($loadingTarget)
            <span wire:loading.remove wire:target="{{ $loadingTarget }}">{{ $slot }}</span>
            <span wire:loading wire:target="{{ $loadingTarget }}" class="flex items-center">
                <svg class="animate-spin inline-block -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $loadingText ?? __('Loading...') }}
            </span>
        @else
            {{ $slot }}
        @endif
    </button>
@endif
