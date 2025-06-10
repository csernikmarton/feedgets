<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class RefreshFeeds extends Command
{
    protected $signature = 'feeds:refresh {--user= : Refresh feeds for a specific user ID} {--uuid= : Refresh a specific feed by UUID}';

    protected $description = 'Refresh all RSS feeds, feeds for a specific user, or a single feed by UUID';

    public function handle()
    {
        $userId = $this->option('user');
        $uuid = $this->option('uuid');

        $query = Feed::query();

        if ($userId) {
            $query->where('user_id', $userId);
            $this->info("Refreshing feeds for user ID: {$userId}");
        }

        if ($uuid) {
            $query->where('uuid', $uuid);
            $this->info("Refreshing feed with UUID: {$uuid}");
        }

        $feeds = $query->get();
        $this->info("Refreshing {$feeds->count()} feeds concurrently using pool");

        $userAgent = config('app.name').' RSS Reader/1.0 ('.config('app.url').')';

        $feedLookup = $feeds->keyBy('uuid');

        $responses = Http::pool(function ($pool) use ($feeds, $userAgent) {
            $requests = [];

            foreach ($feeds as $feed) {
                $this->info("Queuing feed: {$feed->title} (User ID: {$feed->user_id})");

                $requests[$feed->uuid] = $pool->as($feed->uuid)
                    ->timeout(30)
                    ->withUserAgent($userAgent)
                    ->get($feed->url);
            }

            return $requests;
        });

        foreach ($responses as $uuid => $response) {
            $feed = $feedLookup[$uuid];
            $this->info("Processing response for feed: {$feed->title} (User ID: {$feed->user_id})");

            try {
                if (! ($response instanceof ConnectionException) && $response->successful()) {
                    $xml = new SimpleXMLElement($response->body());
                    $this->processRssFeed($feed, $xml);
                } else {
                    $errorMessage = $response instanceof ConnectionException
                        ? 'Connection error: '.$response->getMessage()
                        : 'Failed to fetch feed: HTTP status '.$response->status();

                    $this->error("Failed to fetch feed {$feed->title}: {$errorMessage}");
                    Log::error("Failed to fetch feed {$feed->title} (User ID: {$feed->user_id}): {$errorMessage}");
                }
            } catch (\Exception $e) {
                $this->error("Error processing feed {$feed->title}: {$e->getMessage()}");
                Log::error("Error processing feed {$feed->title} (User ID: {$feed->user_id}): {$e->getMessage()}");
            }
        }

        if ($feeds->count() === 1) {
            $this->info('Feed refreshed successfully');
        } else {
            $this->info('All feeds refreshed successfully');
        }

        return 0;
    }

    private function processRssFeed(Feed $feed, SimpleXMLElement $xml)
    {
        // Handle RSS 2.0
        if (isset($xml->channel)) {
            $oldest_published_at = null;
            foreach ($xml->channel->item as $item) {
                $published_at = isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime((string) $item->pubDate)) : null;
                $article = $this->createOrUpdateArticle($feed, [
                    'title' => (string) $item->title,
                    'description' => (string) $item->description,
                    'link' => (string) $item->link,
                    'guid' => (string) ($item->guid ?? $item->link),
                    'published_at' => $published_at ?? now(),
                ]);
                if ($article->is_read && ($oldest_published_at === null || $published_at < $oldest_published_at)) {
                    $oldest_published_at = $published_at;
                }
            }
            if ($oldest_published_at !== null) {
                $feed->update(['oldest_published_at' => $oldest_published_at]);
            }
        }
        // Handle Atom
        elseif (isset($xml->entry)) {
            $oldest_published_at = null;
            foreach ($xml->entry as $entry) {
                $link = '';
                foreach ($entry->link as $linkObj) {
                    if ((string) $linkObj['rel'] === 'alternate' || empty($linkObj['rel'])) {
                        $link = (string) $linkObj['href'];
                        break;
                    }
                }
                if (empty($link) && isset($entry->link[0])) {
                    $link = (string) $entry->link[0]['href'];
                }

                $published_at = isset($entry->published) ? date('Y-m-d H:i:s', strtotime((string) $entry->published)) : null;
                if ($published_at === null && isset($entry->updated)) {
                    $published_at = date('Y-m-d H:i:s', strtotime((string) $entry->updated));
                }
                $article = $this->createOrUpdateArticle($feed, [
                    'title' => (string) $entry->title,
                    'description' => (string) ($entry->content ?? $entry->summary ?? ''),
                    'link' => $link,
                    'guid' => (string) ($entry->id ?? $link),
                    'published_at' => $published_at ?? now(),
                ]);
                if ($article->is_read && ($oldest_published_at === null || $published_at < $oldest_published_at)) {
                    $oldest_published_at = $published_at;
                }
            }
            if ($oldest_published_at !== null) {
                $feed->update(['oldest_published_at' => $oldest_published_at]);
            }
        }
    }

    private function createOrUpdateArticle(Feed $feed, array $data)
    {
        return Article::updateOrCreate(
            [
                'feed_uuid' => $feed->uuid,
                'guid' => $data['guid'],
            ],
            [
                'title' => html_entity_decode($data['title']),
                'description' => mb_substr(html_entity_decode($data['description']), 0, 1000),
                'link' => $data['link'],
                'published_at' => $data['published_at'],
            ]
        );
    }
}
