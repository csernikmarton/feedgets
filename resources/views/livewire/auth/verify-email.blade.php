<div class="mt-4 flex flex-col gap-6">
    <p class="text-center">
        {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <p class="text-center font-medium !dark:text-green-400 !text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </p>
    @endif

    <div class="flex flex-col items-center justify-between space-y-3">
        <x-primary-button wire:click="sendVerification">
            {{ __('Resend verification email') }}
        </x-primary-button>
        
        <a href="javascript:;" class="text-sm underline" wire:click="logout">
            {{ __('Log out') }}
        </a>
    </div>
</div>
