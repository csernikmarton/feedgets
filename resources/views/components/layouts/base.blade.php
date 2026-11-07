<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
    data-color-scheme="{{ auth()->user()?->color_scheme ?? 'default' }}"
    x-data="{
        theme: localStorage.getItem('theme') || 'system',
        initTheme() {
            const media = window.matchMedia('(prefers-color-scheme: dark)');
            const apply = () => {
                const dark = this.theme === 'dark' || (this.theme === 'system' && media.matches);
                document.documentElement.classList.toggle('dark', dark);
            };

            this.$watch('theme', value => {
                localStorage.setItem('theme', value);
                apply();
            });

            document.addEventListener('livewire:navigated', apply);
            media.addEventListener('change', apply);
        }
    }"
    x-init="initTheme()"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark' || (storedTheme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>[x-cloak] { display: none !important; }</style>

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <title>{{ isset($title) ? $title . ' – ' . config('app.name') : config('app.name') }}</title>

    @php
        $metaTitle = isset($title) ? $title.' – '.config('app.name') : config('app.name');
        $metaDescription = $description ?? __('An open-source RSS dashboard: organise every feed you follow into a customisable board of widgets and read it all in one clean, distraction-free page.');
    @endphp

    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ config('app.name') }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @if(request()->routeIs(['login', 'register', 'password.request', 'password.reset', 'contact']))
        <x-turnstile.scripts />
    @endif
</head>
<body class="font-sans text-gray-900 antialiased min-h-screen bg-gray-100 dark:bg-gray-900" x-cloak>
    @if($layout === 'auth')
        <div class="min-h-screen flex flex-col sm:justify-center items-center mt-6 px-6 sm:px-0">
            <div>
                <a href="/">
                    <x-application-logo-vertical class="w-auto h-20" />
                </a>
            </div>
            <div class="w-full {{ $widthClass ?? 'sm:max-w-md' }} mt-6 mb-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg text-zinc-600 dark:text-zinc-400">
                {{ $slot }}
            </div>
        </div>
    @else
        <div class="flex flex-col min-h-svh bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main class="grow">
                {{ $slot }}
            </main>

            <footer class="bg-white dark:bg-gray-800 shadow mt-auto py-4">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        <span> | </span>
                        @include('footer-links')
                    </div>
                </div>
            </footer>
        </div>
    @endif

    @livewireScripts
</body>
</html>
