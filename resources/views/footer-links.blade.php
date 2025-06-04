@guest
    <a href="{{ route('login') }}" class="underline">{{ __('Log in') }}</a>
    <span> | </span>
    <a href="{{ route('register') }}" class="underline">{{ __('Sign up') }}</a>
@else
    <a href="{{ route('dashboard') }}" class="underline" wire:navigate>{{ __('Dashboard') }}</a>
@endguest

<span> | </span>
<a href="{{ route('faq') }}" class="underline" wire:navigate>{{ __('FAQ') }}</a>
<span> | </span>
<a href="{{ route('contact') }}" class="underline" wire:navigate>{{ __('Contact') }}</a>
<span> | </span>
<a href="https://github.com/csernikmarton/feedgets" target="_blank">
    <img src="/images/github-mark.svg" alt="GitHub" class="h-4 inline-block dark:hidden"/>
    <img src="/images/github-mark-white.svg" alt="GitHub" class="h-4 hidden dark:inline-block"/>
</a>