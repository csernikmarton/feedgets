<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    <x-auth-session-status :status="session('status')"/>

    <form wire:submit="register" class="flex flex-col gap-6">
        <x-text-input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            autocomplete="name"
            :placeholder="__('Full name')"
        />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />

        <x-text-input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />

        <x-text-input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
        />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />

        <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
            <p class="font-medium">{{ __('Password requirements:') }}</p>
            <ul class="list-disc list-inside ml-2 space-y-1">
                <li>{{ __('At least 10 characters long') }}</li>
                <li>{{ __('Must contain uppercase and lowercase letters') }}</li>
                <li>{{ __('Must contain at least one number') }}</li>
                <li>{{ __('Must contain at least one symbol') }}</li>
                <li>{{ __('Cannot be a commonly used password') }}</li>
            </ul>
        </div>

        <x-text-input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
        />
        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />

        <x-embed-turnstile/>

        <div class="flex items-center justify-center">
            <x-primary-button type="submit">{{ __('Create account') }}</x-primary-button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm">
        @include('footer-links')
    </div>
</div>
