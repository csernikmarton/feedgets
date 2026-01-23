<div {{ $attributes->class(['mt-2 flex justify-center']) }}>
    <div wire:ignore>
        <x-turnstile wire:model="turnstileResponse"/>
    </div>
</div>
<x-input-error :messages="$errors->get('turnstileResponse')"/>

@script
<script>
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
