<div>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('API Tokens') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Create personal access tokens to use the Feedgets API from your own scripts or third-party apps. Send the token as a Bearer header: ') }}
            <code class="text-xs">Authorization: Bearer &lt;token&gt;</code>
        </p>
    </header>

    <form wire:submit="createToken" class="mt-6">
        <x-input-label for="tokenName" :value="__('Token name')" />

        <div class="mt-1 flex items-center gap-4">
            <x-text-input wire:model="tokenName" id="tokenName" name="tokenName" type="text" placeholder="{{ __('e.g. My laptop') }}" class="flex-1 !mt-0 h-11" />
            <x-primary-button type="submit" class="h-11 shrink-0">{{ __('Create Token') }}</x-primary-button>
        </div>

        <x-input-error class="mt-2" :messages="$errors->get('tokenName')" />
    </form>

    @if ($plainTextToken)
        <div class="mt-6 rounded-lg border border-green-300 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/30">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ __('Copy your new token now. For your security, it won\'t be shown again.') }}
            </p>
            <code class="mt-2 block cursor-pointer break-all rounded bg-white p-2 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200"
                  x-data x-on:click="navigator.clipboard.writeText('{{ $plainTextToken }}')"
                  title="{{ __('Click to copy') }}">{{ $plainTextToken }}</code>
        </div>
    @endif

    @if (session('token_message'))
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ session('token_message') }}</p>
    @endif

    <div class="mt-6 space-y-2">
        @forelse ($tokens as $token)
            <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $token->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Last used') }}:
                        {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : __('never') }}
                    </p>
                </div>

                <x-danger-button wire:click="deleteToken('{{ $token->id }}')"
                                 wire:confirm="{{ __('Revoke this token? Apps using it will lose access.') }}">
                    {{ __('Revoke') }}
                </x-danger-button>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('You have no API tokens yet.') }}</p>
        @endforelse
    </div>
</div>
