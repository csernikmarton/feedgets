<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('cleanup command deletes old articles', function () {
    // Create a feed with oldest_published_at set
    $feed = Feed::factory()->create([
        'oldest_published_at' => now()->subDays(30),
    ]);

    // Create articles older than oldest_published_at
    $oldArticles = Article::factory()->count(3)->create([
        'feed_uuid' => $feed->uuid,
        'published_at' => now()->subDays(60),
    ]);

    // Create articles newer than oldest_published_at
    $newArticles = Article::factory()->count(2)->create([
        'feed_uuid' => $feed->uuid,
        'published_at' => now()->subDays(15),
    ]);

    // Run the command
    Artisan::call('articles:cleanup');

    // Check that old articles are deleted
    foreach ($oldArticles as $article) {
        $this->assertDatabaseMissing('articles', [
            'uuid' => $article->uuid,
        ]);
    }

    // Check that new articles are kept
    foreach ($newArticles as $article) {
        $this->assertDatabaseHas('articles', [
            'uuid' => $article->uuid,
        ]);
    }
});

test('cleanup command skips feeds with null oldest_published_at', function () {
    // Create a feed with null oldest_published_at
    $feed = Feed::factory()->create([
        'oldest_published_at' => null,
    ]);

    // Create some articles for this feed
    $articles = Article::factory()->count(3)->create([
        'feed_uuid' => $feed->uuid,
        'published_at' => now()->subDays(60),
    ]);

    // Run the command
    Artisan::call('articles:cleanup');

    // Check that all articles are kept
    foreach ($articles as $article) {
        $this->assertDatabaseHas('articles', [
            'uuid' => $article->uuid,
        ]);
    }
});

test('cleanup command returns success code', function () {
    $exitCode = Artisan::call('articles:cleanup');

    expect($exitCode)->toBe(0);
});

test('cleanup command handles exceptions gracefully', function () {
    // Create a test feed
    $feed = new Feed([
        'uuid' => 'test-uuid',
        'title' => 'Test Feed',
        'oldest_published_at' => now()->subDays(30),
    ]);

    // Spy on logging
    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'Simulated deletion failure'));

    // Partial mock of the command to override getFeeds() and deleteOldArticles()
    $command = Mockery::mock(\App\Console\Commands\CleanupOldArticles::class)->makePartial();
    $command->shouldAllowMockingProtectedMethods();
    $command->shouldReceive('getFeeds')->once()->andReturn(collect([$feed]));
    $command->shouldReceive('deleteOldArticles')->once()->with($feed)->andThrow(new \Exception('Simulated deletion failure'));
    $command->shouldReceive('error')->once()->withArgs(fn ($msg) => str_contains($msg, 'Simulated deletion failure'));

    // Run the command
    $result = $command->handle();

    expect($result)->toBe(0);
});
