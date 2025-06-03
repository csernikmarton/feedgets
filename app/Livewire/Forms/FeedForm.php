<?php

namespace App\Livewire\Forms;

use App\Models\Feed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;
use SimpleXMLElement;

use function Illuminate\Support\defer;

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
        $userId = Auth::id();

        if (empty($validated['title'])) {
            $validated['title'] = $this->extractTitleFromFeed($validated['url']);
        }

        $maxPosition = Feed::where('user_id', $userId)->max('position') ?? 0;

        $feed = Feed::create([
            'title' => html_entity_decode($validated['title']),
            'url' => $validated['url'],
            'description' => html_entity_decode($validated['description']),
            'user_id' => $userId,
            'position' => $maxPosition + 1,
        ]);

        defer(function () use ($feed) {
            Artisan::call('feeds:refresh', ['--uuid' => $feed->uuid]);
        });

        return $feed;
    }

    public function update(): Feed
    {
        $validated = $this->validate();

        // If title is empty, try to extract it from the feed
        if (empty($validated['title'])) {
            $validated['title'] = $this->extractTitleFromFeed($validated['url']);
        }

        $this->feed->update($validated);

        $feed = $this->feed;
        defer(function () use ($feed) {
            Artisan::call('feeds:refresh', ['--uuid' => $feed->uuid]);
        });

        return $this->feed;
    }

    private function extractTitleFromFeed(string $url): string
    {
        try {
            $userAgent = config('app.name').' RSS Reader/1.0 ('.config('app.url').')';

            $response = Http::timeout(30)
                ->withUserAgent($userAgent)
                ->get($url);

            if ($response->successful()) {
                $xml = new SimpleXMLElement($response->body());

                // Handle RSS 2.0
                if (isset($xml->channel) && isset($xml->channel->title)) {
                    return html_entity_decode((string) $xml->channel->title);
                }
                // Handle Atom
                elseif (isset($xml->title)) {
                    return html_entity_decode((string) $xml->title);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error extracting title from feed: {$e->getMessage()}");
        }

        return 'Unnamed Feed';
    }
}
