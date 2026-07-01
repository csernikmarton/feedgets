<x-layouts.base :title="trim($__env->yieldContent('code') . ' ' . $__env->yieldContent('title'))" layout="auth">
    <div class="text-center py-6">
        <p class="text-6xl font-semibold text-gray-800 dark:text-gray-200">@yield('code')</p>
        <h1 class="mt-4 text-xl font-medium text-gray-900 dark:text-gray-100">@yield('title')</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">@yield('message')</p>

        <a href="{{ url('/') }}"
           class="mt-6 inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
            {{ __('Go home') }}
        </a>
    </div>

    <script>document.body.removeAttribute('x-cloak')</script>
</x-layouts.base>
