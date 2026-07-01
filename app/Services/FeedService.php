<?php

namespace App\Services;

use App\Models\Feed;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

use function Illuminate\Support\defer;

class FeedService
{
    /**
     * Create a feed for the given user, auto-extracting the title when blank
     * and scheduling a background refresh of its articles.
     *
     * @param  array{title?: ?string, url: string, description?: ?string}  $data
     */
    public function createFeed(User $user, array $data): Feed
    {
        $title = empty($data['title'])
            ? $this->extractTitleFromFeed($data['url'])
            : $data['title'];

        $maxPosition = Feed::where('user_id', $user->id)->max('position') ?? 0;

        $feed = Feed::create([
            'title' => html_entity_decode($title),
            'url' => $data['url'],
            'description' => html_entity_decode($data['description'] ?? ''),
            'user_id' => $user->id,
            'position' => $maxPosition + 1,
        ]);

        $this->scheduleRefresh($feed);

        return $feed;
    }

    /**
     * Update a feed, auto-extracting the title when blank and scheduling a refresh.
     *
     * @param  array{title?: ?string, url: string, description?: ?string}  $data
     */
    public function updateFeed(Feed $feed, array $data): Feed
    {
        if (empty($data['title'])) {
            $data['title'] = $this->extractTitleFromFeed($data['url']);
        }

        $feed->update($data);

        $this->scheduleRefresh($feed);

        return $feed;
    }

    /**
     * Move a feed to a column/index and rebalance positions across all columns.
     */
    public function reorder(Feed $feed, int $toColumn, int $newIndex): void
    {
        DB::transaction(function () use ($feed, $toColumn, $newIndex) {
            $feed->column = $toColumn;
            $feed->save();

            $feedsByColumn = [];
            for ($i = 0; $i <= 2; $i++) {
                $feedsByColumn[$i] = Feed::where('user_id', $feed->user_id)
                    ->where('column', $i)
                    ->whereNot('uuid', $feed->uuid)
                    ->orderBy('position')
                    ->get();
            }

            $targetColumn = $feedsByColumn[$toColumn];
            $feedsByColumn[$toColumn] = $targetColumn->slice(0, $newIndex)
                ->push($feed)
                ->concat($targetColumn->slice($newIndex));

            $position = 1;
            for ($i = 0; $i <= 2; $i++) {
                foreach ($feedsByColumn[$i] as $feedItem) {
                    if ($feedItem->position !== $position) {
                        $feedItem->update(['position' => $position]);
                    }
                    $position++;
                }
            }
        });
    }

    /**
     * Mark every unread article in the feed published at or before the given time as read.
     */
    public function markAllAsRead(Feed $feed, string $publishedAt): void
    {
        $feed->articles()
            ->where('is_read', false)
            ->where('published_at', '<=', $publishedAt)
            ->update(['is_read' => true]);
    }

    /**
     * Fetch a feed's RSS/Atom document and extract its channel title.
     */
    public function extractTitleFromFeed(string $url): string
    {
        try {
            $userAgent = config('app.name').' RSS Reader/1.0 ('.config('app.url').')';

            $response = Http::timeout(30)
                ->withUserAgent($userAgent)
                ->get($url);

            if ($response->successful()) {
                $xml = new SimpleXMLElement($response->body());

                if (isset($xml->channel) && isset($xml->channel->title)) {
                    return html_entity_decode((string) $xml->channel->title);
                } elseif (isset($xml->title)) {
                    return html_entity_decode((string) $xml->title);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error extracting title from feed: {$e->getMessage()}");
        }

        return 'Unnamed Feed';
    }

    private function scheduleRefresh(Feed $feed): void
    {
        defer(function () use ($feed) {
            Artisan::call('feeds:refresh', ['--uuid' => $feed->uuid]);
        });
    }
}
