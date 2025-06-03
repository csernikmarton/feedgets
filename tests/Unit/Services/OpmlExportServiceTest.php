<?php

declare(strict_types=1);

use App\Models\Feed;
use App\Models\User;
use App\Services\OpmlExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('export returns valid OPML XML', function () {
    // Create a user with feeds
    $user = User::factory()->create();
    $feeds = Feed::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    $service = new OpmlExportService;
    $opml = $service->export($user->id);

    // Check that the result is valid XML
    expect($opml)->toBeString();

    $xml = new SimpleXMLElement($opml);

    // Check OPML structure
    expect($xml->getName())->toBe('opml')
        ->and($xml['version']->__toString())->toBe('1.0')
        ->and($xml->head->title->__toString())->toBe('Feedgets Subscriptions Export')
        ->and($xml->body->outline)->toHaveCount(3);

    // Check that all feeds are included
    $feedTitles = $feeds->pluck('title')->toArray();
    $feedUrls = $feeds->pluck('url')->toArray();

    foreach ($xml->body->outline as $outline) {
        expect($feedTitles)->toContain((string) $outline['title'])
            ->and($feedUrls)->toContain((string) $outline['xmlUrl']);
    }
});

test('export handles feeds with description', function () {
    $user = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user->id,
        'description' => 'Test description',
    ]);

    $service = new OpmlExportService;
    $opml = $service->export($user->id);

    $xml = new SimpleXMLElement($opml);

    expect((string) $xml->body->outline[0]['description'])->toBe('Test description');
});

test('export handles user with no feeds', function () {
    $user = User::factory()->create();

    $service = new OpmlExportService;
    $opml = $service->export($user->id);

    $xml = new SimpleXMLElement($opml);

    expect($xml->body->outline)->toHaveCount(0);
});

test('logs and throws an exception if export fails', function () {
    // Spy on the logger
    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'OPML export error: Simulated failure'));

    // Create a partial mock of OpmlExporter
    $exporter = Mockery::mock(OpmlExportService::class)->makePartial();
    $exporter->shouldAllowMockingProtectedMethods();
    $exporter->shouldReceive('getUserFeeds')->with(1)->andThrow(new Exception('Simulated failure'));

    expect(fn () => $exporter->export(1))
        ->toThrow(Exception::class, 'Simulated failure');
});
