@php
    $options = [
        'light' => ['label' => __('Light'), 'icon' => 'M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z'],
        'dark' => ['label' => __('Dark'), 'icon' => 'M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z'],
        'system' => ['label' => __('System'), 'icon' => 'M3 4a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-3v2h2a1 1 0 110 2H6a1 1 0 110-2h2v-2H5a2 2 0 01-2-2V4zm2 0v8h10V4H5z'],
    ];
@endphp

<div {{ $attributes }}>
    <x-dropdown align="right" width="48">
        <x-slot name="trigger">
            <button
                type="button"
                aria-label="{{ __('Theme') }}"
                class="flex items-center justify-center h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-800 focus:ring-indigo-500 transition-colors duration-200 ease-in-out"
            >
                @foreach ($options as $key => $option)
                    <svg x-show="theme === '{{ $key }}'" @if ($key !== 'system') x-cloak @endif xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-800 dark:text-gray-200" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="{{ $option['icon'] }}" clip-rule="evenodd" />
                    </svg>
                @endforeach
            </button>
        </x-slot>

        <x-slot name="content">
            @foreach ($options as $key => $option)
                <button
                    type="button"
                    @click="theme = '{{ $key }}'"
                    :aria-pressed="theme === '{{ $key }}'"
                    class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition duration-150 ease-in-out"
                    :class="theme === '{{ $key }}' ? 'font-semibold' : ''"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="{{ $option['icon'] }}" clip-rule="evenodd" />
                    </svg>
                    <span class="flex-1">{{ $option['label'] }}</span>
                    <svg x-show="theme === '{{ $key }}'" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endforeach
        </x-slot>
    </x-dropdown>
</div>
