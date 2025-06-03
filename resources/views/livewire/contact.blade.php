<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Contact Us')" :description="__('Have a question or feedback? Send us a message.')" />

    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('status') }}</span>
        </div>
    @endif

    <form wire:submit="submit" class="flex flex-col gap-6">
        <x-text-input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            placeholder="Your name"
        />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />

        <x-text-input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            placeholder="email@example.com"
        />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />

        <div>
            <textarea
                id="message"
                wire:model="message"
                class="mt-1 p-2 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                rows="5"
                required
                placeholder="Your message"
            ></textarea>
        </div>
        <x-input-error :messages="$errors->get('message')" class="mt-2" />

        <x-embed-turnstile/>

        <div class="flex items-center justify-center">
            <x-primary-button type="submit">{{ __('Send Message') }}</x-primary-button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm">
        @include('footer-links')
    </div>
</div>
