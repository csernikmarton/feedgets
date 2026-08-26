<x-layouts.base
    layout="app"
    title="Your personal RSS dashboard"
    description="Feedgets is an open-source RSS dashboard. Organise every feed you follow into a customisable, drag-and-drop widget board and read it all in one clean, distraction-free page."
>
    {{-- Hero --}}
    <section class="px-4 sm:px-6 lg:px-8 pt-16 pb-12 sm:pt-24 sm:pb-16">
        <div class="max-w-3xl mx-auto text-center">
            <x-application-logo-vertical class="h-24 w-auto mx-auto mb-8" />

            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ __('All your feeds on one page.') }}
            </h1>

            <p class="mt-6 text-lg leading-relaxed text-gray-600 dark:text-gray-400">
                {{ __('Feedgets is a personalised RSS reader that puts every site and blog you follow into a customisable dashboard of widgets — read it all in one clean, distraction-free place.') }}
            </p>

            <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-md bg-blue-600 text-white font-semibold text-sm hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition">
                    {{ __('Create your free account') }}
                </a>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold text-sm shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition">
                    {{ __('Log in') }}
                </a>
            </div>

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Free to use right here, or self-host it — Feedgets is open source.') }}
            </p>
        </div>
    </section>

    {{-- Screenshot --}}
    <section class="px-4 sm:px-6 lg:px-8 pb-16 sm:pb-24">
        <div class="max-w-5xl mx-auto">
            <div class="relative rounded-xl overflow-hidden aspect-[16/10] ring-1 ring-gray-900/10 dark:ring-white/10 shadow-2xl">
                <img src="{{ asset('images/screenshot-light.jpg') }}" width="1400" height="1451"
                     alt="{{ __('The Feedgets dashboard') }}" class="block w-full dark:hidden">
                <img src="{{ asset('images/screenshot-dark.jpg') }}" width="1400" height="1451"
                     alt="{{ __('The Feedgets dashboard') }}" class="hidden w-full dark:block">
                <div class="absolute inset-x-0 bottom-0 h-32 bg-linear-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="px-4 sm:px-6 lg:px-8 pb-16 sm:pb-24">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl font-bold tracking-tight text-center text-gray-900 dark:text-gray-100">
                {{ __('Everything you missed, and a little more') }}
            </h2>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['M4 5a1 1 0 011-1h5a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1V5z', __('A dashboard you arrange'), __('Three columns of feed widgets you drag, drop and reorder until the page looks the way you think.')],
                    ['M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', __('Never lose your place'), __('Articles are marked read as you go, with unread counts per widget and a one-click "mark everything read" for the whole page.')],
                    ['M12 4v12m0 0l-4-4m4 4l4-4M4 20h16', __('Bring your feeds with you'), __('Import an OPML file from your old reader and be up and running in seconds. Export it again any time — your list is never locked in.')],
                    ['M4 4v5h.6M20 20v-5h-.6M19.4 9A8 8 0 004.6 15M4.6 9A8 8 0 0019.4 15', __('Always up to date'), __('Feeds refresh on a schedule in the background, and old articles are tidied away automatically. Hit refresh yourself whenever you are impatient.')],
                    ['M12 3v1m0 16v1m9-9h-1M4 12H3m15.4-6.4l-.7.7M6.3 17.7l-.7.7m12.8 0l-.7-.7M6.3 6.3l-.7-.7M16 12a4 4 0 11-8 0 4 4 0 018 0z', __('Light, dark, and your colour'), __('A proper dark mode that follows your system, plus a palette picker so the accent colour is one you actually like.')],
                    ['M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', __('Open source, with an API'), __('MIT-licensed and built on Laravel — self-host it if you prefer. A versioned JSON API is there for anything you want to build on top.')],
                ] as [$path, $heading, $body])
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <svg class="h-7 w-7 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $heading }}</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="px-4 sm:px-6 lg:px-8 pb-20">
        <div class="max-w-3xl mx-auto text-center bg-white dark:bg-gray-800 rounded-lg shadow-sm px-6 py-12">
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ __('Get your feeds back in one place') }}
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                {{ __('Sign up, import your OPML, and start reading. It takes about a minute.') }}
            </p>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center px-6 py-3 rounded-md bg-blue-600 text-white font-semibold text-sm hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                    {{ __('Create your free account') }}
                </a>
                <a href="https://github.com/csernikmarton/feedgets" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <img src="/images/github-mark.svg" alt="" class="h-4 dark:hidden">
                    <img src="/images/github-mark-white.svg" alt="" class="h-4 hidden dark:inline-block">
                    {{ __('View on GitHub') }}
                </a>
            </div>
        </div>
    </section>
</x-layouts.base>
