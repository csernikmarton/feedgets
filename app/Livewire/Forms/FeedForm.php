<?php

namespace App\Livewire\Forms;

use App\Models\Feed;
use App\Services\FeedService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FeedForm extends Form
{
    public ?Feed $feed = null;

    #[Validate]
    public string $title = '';

    #[Validate]
    public string $url = '';

    #[Validate]
    public ?string $description = '';

    public function rules()
    {
        return [
            'title' => ['sometimes', 'min:3', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'description' => ['nullable'],
        ];
    }

    public function setFeed(Feed $feed): void
    {
        $this->feed = $feed;
        $this->title = html_entity_decode($feed->title);
        $this->url = $feed->url;
        $this->description = html_entity_decode($feed->description);
    }

    public function store(): Feed
    {
        $validated = $this->validate();

        return app(FeedService::class)->createFeed(Auth::user(), $validated);
    }

    public function update(): Feed
    {
        $validated = $this->validate();

        return app(FeedService::class)->updateFeed($this->feed, $validated);
    }
}
