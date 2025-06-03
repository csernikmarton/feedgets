<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Confirm password')"
        :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
    />

    <x-auth-session-status :status="session('status')"/>

    <form wire:submit="confirmPassword" class="flex flex-col gap-6">
        <x-text-input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
        />

        <div class="flex items-center justify-center">
            <x-primary-button type="submit">{{ __('Confirm') }}</x-primary-button>
        </div>
    </form>
</div>
