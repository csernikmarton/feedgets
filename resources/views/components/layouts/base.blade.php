<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
    x-data="{
        theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        initTheme() {
            function setTheme(value) {
                if (value === undefined || value === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }

            this.$watch('theme', value => {
                localStorage.setItem('theme', value);
                setTheme(value);
            });

            document.addEventListener('livewire:navigated', () => {
                setTheme(this.theme);
            });

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('theme')) {
                    this.theme = e.matches ? 'dark' : 'light';
                }
            });
        }
    }"
    x-init="initTheme()"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
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

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(request()->routeIs(['login', 'register', 'password.request', 'password.reset', 'contact']))
        @turnstileScripts()
    @endif
</head>
<body class="font-sans text-gray-900 antialiased min-h-screen bg-gray-100 dark:bg-gray-900" x-cloak>
    @if($layout === 'auth')
        <div class="min-h-screen flex flex-col sm:justify-center items-center mt-6">
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
</body>
</html>
