<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <x-text-input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <div class="relative">
            <x-text-input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
            />

            @if (Route::has('password.request'))
                <a class="text-sm underline" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
        </div>

        <x-input-error :messages="$errors->get('email')" class="mt-2" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />

        <x-embed-turnstile/>

        <label><input type="checkbox" wire:model="remember"> {{ __('Remember me') }}</label>

        <div class="flex items-center justify-center">
            <x-primary-button type="submit">{{ __('Log in') }}</x-primary-button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm">
        @include('footer-links')
    </div>
</div>
