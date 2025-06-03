<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('refresh command processes RSS feeds', function () {
    // Create a user and feed
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/rss.xml',
    ]);

    // Mock the HTTP pool response
    Http::fake([
        'https://example.com/rss.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Test RSS Feed</title>
    <link>https://example.com</link>
    <description>Test feed description</description>
    <item>
      <title>Test Article 1</title>
      <link>https://example.com/article1</link>
      <description>Article 1 description</description>
      <guid>https://example.com/article1</guid>
      <pubDate>Mon, 01 Jan 2023 12:00:00 GMT</pubDate>
    </item>
    <item>
      <title>Test Article 2</title>
      <link>https://example.com/article2</link>
      <description>Article 2 description</description>
      <guid>https://example.com/article2</guid>
      <pubDate>Tue, 02 Jan 2023 12:00:00 GMT</pubDate>
    </item>
  </channel>
</rss>
XML
            , 200),
    ]);

    // Run the command
    Artisan::call('feeds:refresh');

    // Check that articles were created
    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed->uuid,
        'title' => 'Test Article 1',
        'link' => 'https://example.com/article1',
    ]);

    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed->uuid,
        'title' => 'Test Article 2',
        'link' => 'https://example.com/article2',
    ]);

    // Check that we have 2 articles total
    expect(Article::where('feed_uuid', $feed->uuid)->count())->toBe(2);
});

test('refresh command processes Atom feeds', function () {
    // Create a user and feed
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/atom.xml',
    ]);

    // Mock the HTTP pool response
    Http::fake([
        'https://example.com/atom.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Test Atom Feed</title>
  <link href="https://example.com"/>
  <entry>
    <title>Test Atom Article 1</title>
    <link href="https://example.com/atom1"/>
    <id>https://example.com/atom1</id>
    <content>Atom Article 1 content</content>
    <published>2023-01-01T12:00:00Z</published>
  </entry>
  <entry>
    <title>Test Atom Article 2</title>
    <link href="https://example.com/atom2"/>
    <id>https://example.com/atom2</id>
    <summary>Atom Article 2 summary</summary>
    <updated>2023-01-02T12:00:00Z</updated>
  </entry>
</feed>
XML
            , 200),
    ]);

    // Run the command
    Artisan::call('feeds:refresh');

    // Check that articles were created
    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed->uuid,
        'title' => 'Test Atom Article 1',
    ]);

    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed->uuid,
        'title' => 'Test Atom Article 2',
    ]);

    // Check that we have 2 articles total
    expect(Article::where('feed_uuid', $feed->uuid)->count())->toBe(2);
});

test('refresh command filters by user ID', function () {
    // Create two users with feeds
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $feed1 = Feed::factory()->create([
        'user_id' => $user1->id,
        'url' => 'https://example.com/user1.xml',
    ]);

    $feed2 = Feed::factory()->create([
        'user_id' => $user2->id,
        'url' => 'https://example.com/user2.xml',
    ]);

    // Mock HTTP responses
    Http::fake([
        'https://example.com/user1.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <item>
      <title>User 1 Article</title>
      <link>https://example.com/user1/article</link>
      <guid>https://example.com/user1/article</guid>
    </item>
  </channel>
</rss>
XML
            , 200),
        'https://example.com/user2.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <item>
      <title>User 2 Article</title>
      <link>https://example.com/user2/article</link>
      <guid>https://example.com/user2/article</guid>
    </item>
  </channel>
</rss>
XML
            , 200),
    ]);

    // Run the command with user filter
    Artisan::call('feeds:refresh', ['--user' => $user1->id]);

    // Check that only user1's feed was processed
    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed1->uuid,
        'title' => 'User 1 Article',
    ]);

    $this->assertDatabaseMissing('articles', [
        'feed_uuid' => $feed2->uuid,
        'title' => 'User 2 Article',
    ]);
});

test('refresh command handles HTTP errors', function () {
    // Create a feed with a URL that will return an error
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/error.xml',
    ]);

    // Mock HTTP response with error
    Http::fake([
        'https://example.com/error.xml' => Http::response('Not Found', 404),
    ]);

    // Capture output
    $output = new \Symfony\Component\Console\Output\BufferedOutput;

    // Run the command
    Artisan::call('feeds:refresh', [], $output);

    // Check that error was logged
    $outputText = $output->fetch();
    expect($outputText)->toContain('Failed to fetch feed');

    // Check that no articles were created
    expect(Article::where('feed_uuid', $feed->uuid)->count())->toBe(0);
});

test('refresh command handles XML parsing errors', function () {
    // Create a feed with a URL that will return invalid XML
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/invalid.xml',
    ]);

    // Mock HTTP response with invalid XML
    Http::fake([
        'https://example.com/invalid.xml' => Http::response('This is not XML', 200),
    ]);

    // Capture output
    $output = new \Symfony\Component\Console\Output\BufferedOutput;

    // Run the command
    Artisan::call('feeds:refresh', [], $output);

    // Check that error was logged
    $outputText = $output->fetch();
    expect($outputText)->toContain('Error processing feed');

    // Check that no articles were created
    expect(Article::where('feed_uuid', $feed->uuid)->count())->toBe(0);
});

test('refresh command returns success code', function () {
    $exitCode = Artisan::call('feeds:refresh');

    expect($exitCode)->toBe(0);
});

test('refresh command filters by feed UUID', function () {
    // Create two feeds
    $user = User::factory()->create();

    $feed1 = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/feed1.xml',
    ]);

    $feed2 = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/feed2.xml',
    ]);

    // Mock HTTP responses
    Http::fake([
        'https://example.com/feed1.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <item>
      <title>Feed 1 Article</title>
      <link>https://example.com/feed1/article</link>
      <guid>https://example.com/feed1/article</guid>
    </item>
  </channel>
</rss>
XML
            , 200),
        'https://example.com/feed2.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <item>
      <title>Feed 2 Article</title>
      <link>https://example.com/feed2/article</link>
      <guid>https://example.com/feed2/article</guid>
    </item>
  </channel>
</rss>
XML
            , 200),
    ]);

    // Capture output
    $output = new \Symfony\Component\Console\Output\BufferedOutput;

    // Run the command with UUID filter
    Artisan::call('feeds:refresh', ['--uuid' => $feed1->uuid], $output);

    // Check output
    $outputText = $output->fetch();
    expect($outputText)->toContain('Refreshing feed with UUID: '.$feed1->uuid);
    expect($outputText)->toContain('Feed refreshed successfully');

    // Check that only feed1 was processed
    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed1->uuid,
        'title' => 'Feed 1 Article',
    ]);

    $this->assertDatabaseMissing('articles', [
        'feed_uuid' => $feed2->uuid,
        'title' => 'Feed 2 Article',
    ]);
});

test('refresh command updates oldest_published_at for RSS feeds with read articles', function () {
    // Create a user and feed
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/rss-with-read.xml',
        'oldest_published_at' => null,
    ]);

    // Mock the HTTP pool response
    Http::fake([
        'https://example.com/rss-with-read.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Test RSS Feed</title>
    <link>https://example.com</link>
    <description>Test feed description</description>
    <item>
      <title>Newer Article</title>
      <link>https://example.com/newer-article</link>
      <description>Newer article description</description>
      <guid>https://example.com/newer-article</guid>
      <pubDate>Mon, 02 Jan 2023 12:00:00 GMT</pubDate>
    </item>
    <item>
      <title>Older Article</title>
      <link>https://example.com/older-article</link>
      <description>Older article description</description>
      <guid>https://example.com/older-article</guid>
      <pubDate>Sun, 01 Jan 2023 12:00:00 GMT</pubDate>
    </item>
  </channel>
</rss>
XML
            , 200),
    ]);

    // Run the command to create articles
    Artisan::call('feeds:refresh');

    // Mark the older article as read
    $olderArticle = Article::where('guid', 'https://example.com/older-article')->first();
    $olderArticle->markAsRead();

    // Run the command again to update oldest_published_at
    Artisan::call('feeds:refresh');

    // Refresh the feed from the database
    $feed->refresh();

    // Check that oldest_published_at was updated to the older article's date
    expect($feed->oldest_published_at->format('Y-m-d H:i:s'))->toBe('2023-01-01 12:00:00');

    /*
    // Run the command to create articles
    Artisan::call('feeds:refresh');

    // Check articles after first refresh
    $olderArticle = Article::where('guid', 'https://example.com/older-article')->first();
    $newerArticle = Article::where('guid', 'https://example.com/newer-article')->first();

    expect($olderArticle->is_read)->toBeFalse();
    expect($newerArticle->is_read)->toBeFalse();

    // Mark the older article as read
    $olderArticle->markAsRead();

    // Verify the older article is marked as read
    $olderArticle->refresh();
    expect($olderArticle->is_read)->toBeTrue();

    // Run the command again to update oldest_published_at
    Artisan::call('feeds:refresh');

    // Check articles after second refresh
    $olderArticle->refresh();
    $newerArticle->refresh();

    // Verify the older article is still marked as read
    expect($olderArticle->is_read)->toBeTrue();
    expect($newerArticle->is_read)->toBeFalse();

    // Check what's in the database directly
    $readArticles = Article::where('feed_uuid', $feed->uuid)
        ->where('is_read', true)
        ->orderBy('published_at')
        ->get();

    dump("Read articles count: " . $readArticles->count());
    foreach ($readArticles as $article) {
        dump("Read article: {$article->title}, published_at: {$article->published_at->format('Y-m-d H:i:s')}, guid: {$article->guid}");
    }

    // Check what the feed's oldest_published_at is directly from the database
    $feedFromDb = \DB::table('feeds')->where('uuid', $feed->uuid)->first();
    dump("Feed oldest_published_at from DB: " . $feedFromDb->oldest_published_at);

    // Refresh the feed from the database
    $feed->refresh();

    // Check that oldest_published_at was updated to the older article's date
    expect($feed->oldest_published_at->format('Y-m-d H:i:s'))->toBe('2023-01-01 12:00:00');
    */
});

test('refresh command processes Atom feeds with fallback link', function () {
    // Create a user and feed
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/atom-fallback.xml',
    ]);

    // Mock the HTTP pool response with an Atom feed that has links without rel attributes
    Http::fake([
        'https://example.com/atom-fallback.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Test Atom Feed with Fallback Links</title>
  <link href="https://example.com"/>
  <entry>
    <title>Fallback Link Article</title>
    <link href="https://example.com/fallback-article"/>
    <id>https://example.com/fallback-article</id>
    <content>Article with fallback link</content>
    <published>2023-01-01T12:00:00Z</published>
  </entry>
</feed>
XML
            , 200),
    ]);

    // Run the command
    Artisan::call('feeds:refresh');

    // Check that the article was created with the correct link
    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed->uuid,
        'title' => 'Fallback Link Article',
        'link' => 'https://example.com/fallback-article',
    ]);
});

test('refresh command processes Atom feeds with empty link and fallback to first link element', function () {
    // Create a user and feed
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/atom-empty-link-fallback.xml',
    ]);

    // Mock the HTTP pool response with an Atom feed that has no link with rel="alternate"
    // but has a link element that can be used as fallback (testing line 108 in RefreshFeeds.php)
    Http::fake([
        'https://example.com/atom-empty-link-fallback.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Test Atom Feed with Empty Link Fallback</title>
  <link href="https://example.com"/>
  <entry>
    <title>Empty Link Fallback Article</title>
    <link href="https://example.com/special-link" rel="enclosure"/>
    <id>https://example.com/empty-link-fallback-article</id>
    <content>Article with empty link and fallback to first link element</content>
    <published>2023-01-01T12:00:00Z</published>
  </entry>
</feed>
XML
            , 200),
    ]);

    // Run the command
    Artisan::call('feeds:refresh');

    // Check that the article was created with the correct link from the fallback
    $this->assertDatabaseHas('articles', [
        'feed_uuid' => $feed->uuid,
        'title' => 'Empty Link Fallback Article',
        'link' => 'https://example.com/special-link', // This should be taken from the first link element
    ]);
});

test('refresh command updates oldest_published_at for Atom feeds with read articles', function () {
    // Create a user and feed
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com/atom-with-read.xml',
        'oldest_published_at' => null,
    ]);

    // Mock the HTTP pool response
    Http::fake([
        'https://example.com/atom-with-read.xml' => Http::response(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Test Atom Feed</title>
  <link href="https://example.com"/>
  <entry>
    <title>Newer Atom Article</title>
    <link href="https://example.com/newer-atom-article" rel="alternate"/>
    <id>https://example.com/newer-atom-article</id>
    <content>Newer atom article content</content>
    <published>2023-01-02T12:00:00Z</published>
  </entry>
  <entry>
    <title>Older Atom Article</title>
    <link href="https://example.com/older-atom-article" rel="alternate"/>
    <id>https://example.com/older-atom-article</id>
    <content>Older atom article content</content>
    <published>2023-01-01T12:00:00Z</published>
  </entry>
</feed>
XML
            , 200),
    ]);

    // Run the command to create articles
    Artisan::call('feeds:refresh');

    // Mark the older article as read
    $olderArticle = Article::where('guid', 'https://example.com/older-atom-article')->first();
    $olderArticle->markAsRead();

    // Run the command again to update oldest_published_at
    Artisan::call('feeds:refresh');

    // Refresh the feed from the database
    $feed->refresh();

    // Check that oldest_published_at was updated to the older article's date
    expect($feed->oldest_published_at->format('Y-m-d H:i:s'))->toBe('2023-01-01 12:00:00');
});
