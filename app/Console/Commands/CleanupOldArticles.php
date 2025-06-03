<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOldArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old articles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $feeds = $this->getFeeds();

        foreach ($feeds as $feed) {
            if ($feed->oldest_published_at === null) {
                continue;
            }

            try {
                $deleted = $this->deleteOldArticles($feed);
                $this->info("Successfully deleted {$deleted} old articles for feed: {$feed->title}");
            } catch (\Exception $e) {
                $this->error("Error deleting old articles for feed: {$feed->title} - {$e->getMessage()}");
                Log::error("Error in articles:cleanup command: {$e->getMessage()}");
            }
        }

        return 0;
    }

    protected function getFeeds()
    {
        return Feed::all();
    }

    protected function deleteOldArticles($feed)
    {
        return Article::query()
            ->where('feed_uuid', $feed->uuid)
            ->where('published_at', '<', $feed->oldest_published_at)
            ->delete();
    }
}
