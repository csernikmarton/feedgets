<?php

use App\Models\Feed;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Importing defers a feeds:refresh run; keep it off the network.
    Http::fake(['*' => Http::response('<rss><channel><title>Faked</title></channel></rss>', 200)]);

    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

function opmlFixture(string $url = 'https://example.com/imported.xml'): UploadedFile
{
    $xml = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <opml version="2.0">
        <head><title>Subscriptions</title></head>
        <body>
            <outline text="Imported" title="Imported" type="rss" xmlUrl="{$url}"/>
        </body>
    </opml>
    XML;

    return UploadedFile::fake()->createWithContent('subscriptions.xml', $xml);
}

test('opml import creates feeds for the user', function () {
    $this->post('/api/v1/opml/import', ['file' => opmlFixture()])
        ->assertOk()
        ->assertJsonPath('count', 1);

    $this->assertDatabaseHas('feeds', [
        'url' => 'https://example.com/imported.xml',
        'user_id' => $this->user->id,
    ]);
});

test('opml import validates the uploaded file', function () {
    $this->postJson('/api/v1/opml/import', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('opml export returns the user\'s feeds as xml', function () {
    Feed::factory()->for($this->user)->create(['url' => 'https://example.com/mine.xml']);
    Feed::factory()->for(User::factory())->create(['url' => 'https://example.com/theirs.xml']);

    $response = $this->get('/api/v1/opml/export');

    $response->assertOk()->assertHeader('content-type', 'application/xml');
    expect($response->getContent())
        ->toContain('https://example.com/mine.xml')
        ->not->toContain('https://example.com/theirs.xml');
});
