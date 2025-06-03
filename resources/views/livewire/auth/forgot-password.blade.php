<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

    <x-auth-session-status :status="session('status')"/>

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <x-text-input
                wire:model="email"
                :label="__('Email Address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
        />

        <x-input-error :messages="$errors->get('email')" class="mt-2" />

        <x-embed-turnstile/>

        <div class="flex items-center justify-center">
            <x-primary-button type="submit">{{ __('Email password reset link') }}</x-primary-button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm">
        @include('footer-links')
    </div>
</div>
