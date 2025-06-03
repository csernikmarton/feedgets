@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="font-bold text-xl mb-2">{{ $title }}</h1>
    <p>{{ $description }}</p>
</div>
