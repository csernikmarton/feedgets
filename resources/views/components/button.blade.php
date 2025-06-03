@props([
    'type' => 'button',
    'color' => 'secondary',
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
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif