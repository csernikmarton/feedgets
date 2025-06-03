<?php

declare(strict_types=1);

use App\Models\Feed;
use App\Models\User;
use App\Services\OpmlImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('import creates feeds from valid OPML', function () {
    $user = User::factory()->create();
    $service = new OpmlImportService;

    $validOpml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
  <head>
    <title>Test Subscriptions</title>
  </head>
  <body>
    <outline text="Tech News" title="Tech News" type="rss" xmlUrl="https://example.com/tech.rss" description="Tech news feed"/>
    <outline text="Science" title="Science" type="rss" xmlUrl="https://example.com/science.rss"/>
  </body>
</opml>
XML;

    $result = $service->import($validOpml, $user->id);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(2);

    $this->assertDatabaseHas('feeds', [
        'user_id' => $user->id,
        'title' => 'Tech News',
        'url' => 'https://example.com/tech.rss',
        'description' => 'Tech news feed',
    ]);

    $this->assertDatabaseHas('feeds', [
        'user_id' => $user->id,
        'title' => 'Science',
        'url' => 'https://example.com/science.rss',
    ]);
});

test('import handles nested folders in OPML', function () {
    $user = User::factory()->create();
    $service = new OpmlImportService;

    $nestedOpml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
  <head>
    <title>Test Subscriptions</title>
  </head>
  <body>
    <outline text="Technology">
      <outline text="Programming" title="Programming" type="rss" xmlUrl="https://example.com/programming.rss"/>
    </outline>
  </body>
</opml>
XML;

    $result = $service->import($nestedOpml, $user->id);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1);

    $this->assertDatabaseHas('feeds', [
        'user_id' => $user->id,
        'title' => 'Programming',
        'url' => 'https://example.com/programming.rss',
    ]);
});

test('import skips duplicate feeds', function () {
    $user = User::factory()->create();

    // Create a feed that already exists
    Feed::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'title' => 'Existing Feed',
        'url' => 'https://example.com/existing.rss',
        'position' => 1,
    ]);

    $service = new OpmlImportService;

    $opmlWithDuplicate = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
  <body>
    <outline text="Existing Feed" title="Existing Feed" type="rss" xmlUrl="https://example.com/existing.rss"/>
    <outline text="New Feed" title="New Feed" type="rss" xmlUrl="https://example.com/new.rss"/>
  </body>
</opml>
XML;

    $result = $service->import($opmlWithDuplicate, $user->id);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1); // Only one new feed should be imported

    // Check that we have 2 feeds total (1 existing + 1 new)
    expect(Feed::where('user_id', $user->id)->count())->toBe(2);
});

test('import handles invalid XML', function () {
    $user = User::factory()->create();
    $service = new OpmlImportService;

    $invalidXml = 'This is not XML';

    $result = $service->import($invalidXml, $user->id);

    expect($result['success'])->toBeFalse()
        ->and($result['count'])->toBe(0);
});

test('import handles XML without body', function () {
    $user = User::factory()->create();
    $service = new OpmlImportService;

    $invalidOpml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
  <head>
    <title>Test Subscriptions</title>
  </head>
</opml>
XML;

    $result = $service->import($invalidOpml, $user->id);

    expect($result['success'])->toBeFalse()
        ->and($result['count'])->toBe(0);
});

test('import handles XML that fails to load but does not throw exception', function () {
    $user = User::factory()->create();
    $service = new OpmlImportService;

    // This is a special case where simplexml_load_string() returns false but doesn't throw an exception
    // Using a string that looks like XML but has issues that cause simplexml_load_string to return false
    $problematicXml = '<?xml version="1.0" encoding="UTF-8"?><opml><invalid>';

    $result = $service->import($problematicXml, $user->id);

    expect($result['success'])->toBeFalse()
        ->and($result['count'])->toBe(0)
        ->and($result['message'])->toContain('Error processing OPML file:');
});

test('import handles empty XML string', function () {
    $user = User::factory()->create();
    $service = new OpmlImportService;

    // An empty string will cause simplexml_load_string() to return false without throwing an exception
    $emptyXml = '';

    $result = $service->import($emptyXml, $user->id);

    expect($result['success'])->toBeFalse()
        ->and($result['count'])->toBe(0)
        ->and($result['message'])->toBe('Invalid OPML file format.');
});

test('returns error response if parseAndImportFeeds throws', function () {
    $validOpml = <<<'OPML'
<?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
    <head>
        <title>Sample</title>
    </head>
    <body>
        <outline text="Example Feed" title="Example Feed" type="rss" xmlUrl="https://example.com/rss"/>
    </body>
</opml>
OPML;

    // Expect log to be called
    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'OPML import error (Service 2): Simulated failure'));

    // Partial mock to simulate failure
    $service = Mockery::mock(OpmlImportService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('parseAndImportFeeds')->once()->andThrow(new Exception('Simulated failure'));

    $result = $service->import($validOpml, 1);

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'Error processing OPML file: Simulated failure',
        'count' => 0,
    ]);
});

test('calls feeds:refresh artisan command when feeds are imported successfully', function () {
    $user = User::factory()->create();
    $validOpml = <<<'OPML'
<?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
    <head>
        <title>Sample</title>
    </head>
    <body>
        <outline text="Example Feed" title="Example Feed" type="rss" xmlUrl="https://example.com/rss"/>
    </body>
</opml>
OPML;

    // Mock Artisan facade to verify it's called with the correct parameters
    Artisan::shouldReceive('call')
        ->once()
        ->with('feeds:refresh', ['--user' => $user->id]);

    $service = new OpmlImportService;
    $result = $service->import($validOpml, $user->id);

    // Execute deferred callbacks to trigger the Artisan command
    app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1);

    // Verify that a feed was created
    $this->assertDatabaseHas('feeds', [
        'user_id' => $user->id,
        'title' => 'Example Feed',
        'url' => 'https://example.com/rss',
    ]);
});
