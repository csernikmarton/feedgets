<?php

use App\Livewire\ManageFeeds;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('manage feeds page can be rendered', function () {
    $response = $this->get('/feeds/manage');
    $response->assertStatus(200);
});

test('component lists feeds for authenticated user', function () {
    // Create feeds for the current user
    $userFeeds = Feed::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    // Create feeds for another user
    $otherUser = User::factory()->create();
    $otherUserFeeds = Feed::factory()->count(2)->create([
        'user_id' => $otherUser->id,
    ]);

    // Test the component
    $component = Livewire::test(ManageFeeds::class);

    // Get the feeds from the component
    $feeds = $component->viewData('feeds');

    // Verify only the current user's feeds are included
    expect($feeds)->toHaveCount(3);
    foreach ($userFeeds as $feed) {
        expect($feeds->pluck('uuid')->contains($feed->uuid))->toBeTrue();
    }

    foreach ($otherUserFeeds as $feed) {
        expect($feeds->pluck('uuid')->contains($feed->uuid))->toBeFalse();
    }
});

test('component can create a new feed', function () {
    $component = Livewire::test(ManageFeeds::class)
        ->call('create')
        ->assertSet('editingFeedId', null)
        ->set('form.title', 'Test Feed')
        ->set('form.url', 'https://example.com/feed')
        ->set('form.description', 'Test Description')
        ->call('save')
        ->assertDispatched('feeds-updated');
    expect($component)->assertFlashMessageHas('message', 'Feed added successfully.');

    // Verify feed was created in the database
    $this->assertDatabaseHas('feeds', [
        'title' => 'Test Feed',
        'url' => 'https://example.com/feed',
        'description' => 'Test Description',
        'user_id' => $this->user->id,
    ]);
});

test('component prevents duplicate feeds', function () {
    // Create a feed
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com/feed',
    ]);

    // Try to create another feed with the same URL
    $component = Livewire::test(ManageFeeds::class)
        ->call('create')
        ->set('form.title', 'Duplicate Feed')
        ->set('form.url', 'https://example.com/feed')
        ->set('form.description', 'Duplicate Description')
        ->call('save');
    expect($component)->assertFlashMessageHas('message', 'This feed has already been added.');

    // Verify no duplicate feed was created
    $this->assertDatabaseCount('feeds', 1);
});

test('component can edit a feed', function () {
    // Create a feed
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Original Title',
        'url' => 'https://example.com/feed',
        'description' => 'Original Description',
    ]);

    // Edit the feed
    $component = Livewire::test(ManageFeeds::class)
        ->call('edit', $feed)
        ->assertSet('editingFeedId', $feed->uuid)
        ->assertSet('form.title', 'Original Title')
        ->assertSet('form.url', 'https://example.com/feed')
        ->assertSet('form.description', 'Original Description')
        ->set('form.title', 'Updated Title')
        ->set('form.description', 'Updated Description')
        ->call('save')
        ->assertDispatched('feeds-updated')
        ->assertSet('editingFeedId', null);
    expect($component)->assertFlashMessageHas('message', 'Feed updated successfully.');

    // Verify feed was updated in the database
    $this->assertDatabaseHas('feeds', [
        'uuid' => $feed->uuid,
        'title' => 'Updated Title',
        'url' => 'https://example.com/feed',
        'description' => 'Updated Description',
    ]);
});

test('component prevents editing feeds of other users', function () {
    // Create a feed for another user
    $otherUser = User::factory()->create();
    $otherUserFeed = Feed::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    // Try to edit the feed
    $component = Livewire::test(ManageFeeds::class)
        ->call('edit', $otherUserFeed);
    expect($component)->assertFlashMessageHas('message', 'You do not have permission to edit this feed.');
});

test('component prevents saving feeds of other users', function () {
    // Create a feed for another user
    $otherUser = User::factory()->create();
    $otherUserFeed = Feed::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    // Try to edit the feed
    $component = Livewire::test(ManageFeeds::class)
        ->set('editingFeedId', $otherUserFeed->uuid)
        ->call('save', $otherUserFeed);
    expect($component)->assertFlashMessageHas('message', 'You do not have permission to update this feed.');
});

test('component can delete a feed', function () {
    // Create a feed
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Delete the feed
    $component = Livewire::test(ManageFeeds::class)
        ->call('delete', $feed)
        ->assertDispatched('feeds-updated');
    expect($component)->assertFlashMessageHas('message', 'Feed deleted successfully.');

    // Verify feed was deleted from the database
    $this->assertDatabaseMissing('feeds', [
        'uuid' => $feed->uuid,
    ]);
});

test('component prevents deleting feeds of other users', function () {
    // Create a feed for another user
    $otherUser = User::factory()->create();
    $otherUserFeed = Feed::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    // Try to delete the feed
    $component = Livewire::test(ManageFeeds::class)
        ->call('delete', $otherUserFeed);
    expect($component)->assertFlashMessageHas('message', 'You do not have permission to delete this feed.');

    // Verify feed was not deleted
    $this->assertDatabaseHas('feeds', [
        'uuid' => $otherUserFeed->uuid,
    ]);
});

test('component resets editing state when deleting the currently edited feed', function () {
    // Create a feed
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Set the feed as the currently edited feed
    $component = Livewire::test(ManageFeeds::class)
        ->call('edit', $feed)
        ->assertSet('editingFeedId', $feed->uuid);

    // Delete the feed that's currently being edited
    $component->call('delete', $feed)
        ->assertSet('editingFeedId', null)
        ->assertDispatched('feeds-updated');

    // Verify feed was deleted from the database
    $this->assertDatabaseMissing('feeds', [
        'uuid' => $feed->uuid,
    ]);
});

test('component can import OPML file', function () {
    // Mock the OpmlImportService
    $this->mock(\App\Services\OpmlImportService::class, function ($mock) {
        $mock->shouldReceive('import')
            ->once()
            ->andReturn([
                'success' => true,
                'count' => 3,
                'message' => '',
            ]);
    });

    // Create a fake OPML file with content
    Storage::fake('local');
    $file = UploadedFile::fake()->create('feeds.opml', '<?xml version="1.0" encoding="UTF-8"?><opml version="1.0"><body><outline text="Example" xmlUrl="http://example.com/feed.xml"/></body></opml>', 'text/xml');

    // Import the file
    $component = Livewire::test(ManageFeeds::class)
        ->set('opmlFile', $file)
        ->call('importOpml')
        ->assertDispatched('feeds-updated');

    // Verify the flash message was set
    expect($component)->assertFlashMessageHas('message', 'Successfully imported 3 feed(s) from OPML file.');
});

test('component handles OPML import errors', function () {
    // Mock the OpmlImportService to return an error
    $this->mock(\App\Services\OpmlImportService::class, function ($mock) {
        $mock->shouldReceive('import')
            ->once()
            ->andReturn([
                'success' => false,
                'count' => 0,
                'message' => 'Invalid OPML format',
            ]);
    });

    // Create a fake OPML file with content
    Storage::fake('local');
    $file = UploadedFile::fake()->create('feeds.opml', '<?xml version="1.0" encoding="UTF-8"?><opml version="1.0"><body><outline text="Example" xmlUrl="http://example.com/feed.xml"/></body></opml>', 'text/xml');

    // Import the file
    $component = Livewire::test(ManageFeeds::class)
        ->set('opmlFile', $file)
        ->call('importOpml');
    expect($component)->assertFlashMessageHas('error', 'Failed to import OPML file: Invalid OPML format');
});

test('component handles empty OPML file contents', function () {
    // Create a spy to monitor file_get_contents calls
    $this->spy('file_get_contents')->andReturn(false);

    // Create a fake OPML file
    Storage::fake('local');
    $file = UploadedFile::fake()->create('empty.opml', '', 'text/xml');

    // Import the file
    $component = Livewire::test(ManageFeeds::class)
        ->set('opmlFile', $file)
        ->call('importOpml');

    // Verify the error message is shown
    expect($component)->assertFlashMessageHas('error', 'An error occurred while importing the OPML file: Could not read OPML file contents');
});

test('component can export OPML file', function () {
    // Create a mock of OpmlExportService
    $mockExporter = Mockery::mock(\App\Services\OpmlExportService::class);
    $mockExporter->shouldReceive('export')
        ->once()
        ->with($this->user->id) // Ensure it's called with the correct user ID
        ->andReturn('<?xml version="1.0" encoding="UTF-8"?><opml version="1.0"></opml>');

    // Bind the mock to the container
    app()->instance(\App\Services\OpmlExportService::class, $mockExporter);

    // Mock Auth facade to return the test user's ID
    \Illuminate\Support\Facades\Auth::shouldReceive('id')
        ->andReturn($this->user->id);

    // Create a component instance directly to test the method
    $component = new ManageFeeds;
    $component->form = new \App\Livewire\Forms\FeedForm($component, 'form');
    $response = $component->exportOpml();

    // Verify the response is a download
    $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
    $this->assertEquals('attachment; filename=feedgets_subscriptions_'.date('Y-m-d').'.opml', $response->headers->get('Content-Disposition'));
});

test('component streams OPML content in the response', function () {
    // Expected OPML content
    $expectedOpmlContent = '<?xml version="1.0" encoding="UTF-8"?><opml version="1.0"><head><title>Test Export</title></head><body></body></opml>';

    // Create a mock of OpmlExportService
    $mockExporter = Mockery::mock(\App\Services\OpmlExportService::class);
    $mockExporter->shouldReceive('export')
        ->once()
        ->with($this->user->id)
        ->andReturn($expectedOpmlContent);

    // Bind the mock to the container
    app()->instance(\App\Services\OpmlExportService::class, $mockExporter);

    // Mock Auth facade to return the test user's ID
    \Illuminate\Support\Facades\Auth::shouldReceive('id')
        ->andReturn($this->user->id);

    // Create a component instance
    $component = new ManageFeeds;
    $component->form = new \App\Livewire\Forms\FeedForm($component, 'form');

    // Capture the output of the streamed response
    ob_start();
    $response = $component->exportOpml();
    $response->sendContent();
    $actualContent = ob_get_clean();

    // Verify the streamed content matches the expected OPML content
    $this->assertEquals($expectedOpmlContent, $actualContent);
});

test('component logs error and flashes session error when OPML export fails', function () {
    // Mock the export service to throw an exception
    $mockExporter = Mockery::mock(\App\Services\OpmlExportService::class);
    $mockExporter->shouldReceive('export')
        ->once()
        ->with(1)
        ->andThrow(new Exception('Mocked export exception'));

    app()->instance(\App\Services\OpmlExportService::class, $mockExporter);

    // Fake Auth
    Auth::shouldReceive('id')->andReturn(1);

    // Spy on Log and Session
    Log::spy();
    Session::spy();

    Livewire::test(ManageFeeds::class)
        ->call('exportOpml');

    // Assert flash error was called with correct message
    Session::shouldHaveReceived('flash')
        ->with('error', 'An error occurred while exporting your feeds: Mocked export exception')
        ->once();

    // Assert log captured the error
    Log::shouldHaveReceived('error')
        ->withArgs(fn ($message) => str_contains($message, 'OPML export error') &&
            str_contains($message, 'Mocked export exception')
        )
        ->once();
});
