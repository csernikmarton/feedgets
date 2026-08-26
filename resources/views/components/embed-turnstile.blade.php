<div {{ $attributes->class(['mt-2 flex justify-center']) }}>
    <div wire:ignore>
        <x-turnstile wire:model="turnstileResponse"/>
    </div>
</div>
<x-input-error :messages="$errors->get('turnstileResponse')"/>

@script
<script>
    // The package registers these on livewire:initialized, which never fires again after a
    // wire:navigate visit, so the callback stays undefined or keeps pointing at the previous
    // page's component. This block re-registers them on every component boot.
    window.captchaCallback = (token) => $wire.set('turnstileResponse', token);
    window.captchaExpiredCallback = () => turnstile.reset();

    refreshTurnstile();

    function refreshTurnstile() {
        setTimeout(function () {
            const turnstileElements = document.getElementsByClassName('cf-turnstile');
            for (let i = 0; i < turnstileElements.length; i++) {
                turnstile.remove(turnstileElements[i]);
                turnstile.render(turnstileElements[i]);
            }
        }, 100);
    }

    $wire.on('refresh-turnstile', () => {
        refreshTurnstile();
    });
</script>
@endscript
