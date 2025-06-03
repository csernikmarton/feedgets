<?php

use App\Livewire\Contact;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

beforeEach(function () {
    // Mock the Turnstile validation to always pass
    $this->mock(Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });
});

test('contact component can be rendered', function () {
    Livewire::test(Contact::class)
        ->assertSuccessful();
});

test('contact form validates required fields', function () {
    Livewire::test(Contact::class)
        ->set('turnstileResponse', 'valid-response')
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'message']);
});

test('contact form validates email format', function () {
    Livewire::test(Contact::class)
        ->set('name', 'Test User')
        ->set('email', 'not-an-email')
        ->set('message', 'This is a test message that is long enough to pass validation.')
        ->set('turnstileResponse', 'valid-response')
        ->call('submit')
        ->assertHasErrors(['email']);
});

test('contact form validates message length', function () {
    Livewire::test(Contact::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('message', 'Too short')
        ->set('turnstileResponse', 'valid-response')
        ->call('submit')
        ->assertHasErrors(['message']);
});

test('contact form validates turnstile response', function () {
    Livewire::test(Contact::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('message', 'This is a test message that is long enough to pass validation.')
        ->call('submit')
        ->assertHasErrors(['turnstileResponse']);
});

test('contact form sends email when valid', function () {
    // Set up the admin email configuration
    config(['admin.email' => 'admin@example.com']);

    // Spy on the Mail facade
    Mail::spy();

    // Test the component
    Livewire::test(Contact::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('message', 'This is a test message that is long enough to pass validation.')
        ->set('turnstileResponse', 'valid-response')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('refresh-turnstile');

    // Verify that Mail::raw was called
    Mail::shouldHaveReceived('raw')
        ->once()
        ->withArgs(function ($message, $callback) {
            // Check that the message contains the expected content
            $containsName = str_contains($message, 'Name: Test User');
            $containsEmail = str_contains($message, 'Email: test@example.com');
            $containsMessage = str_contains($message, 'Message: This is a test message that is long enough to pass validation.');

            // We can't easily check the callback function without creating a Message instance,
            // so we'll just check that the message content is correct
            return $containsName && $containsEmail && $containsMessage;
        });
});

test('contact form shows success message after submission', function () {
    Mail::fake();

    $component = Livewire::test(Contact::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('message', 'This is a test message that is long enough to pass validation.')
        ->set('turnstileResponse', 'valid-response')
        ->call('submit')
        ->assertHasNoErrors();

    expect($component)->assertFlashMessageHas('status', 'Your message has been sent successfully!');
});

test('contact form resets after submission', function () {
    Mail::fake();

    Livewire::test(Contact::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('message', 'This is a test message that is long enough to pass validation.')
        ->set('turnstileResponse', 'valid-response')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('name', '')
        ->assertSet('email', '')
        ->assertSet('message', '')
        ->assertSet('turnstileResponse', '');
});
