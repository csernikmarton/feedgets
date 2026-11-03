<?php

use App\Mail\ExceptionMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportFileUploads\MissingFileUploadsTraitException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

beforeEach(function () {
    Mail::fake();
    config(['admin.email' => 'admin@example.com']);
    $this->app['env'] = 'production';
});

it('does not email bot-triggered Livewire exceptions', function (Closure $throwException, int $expectedStatus) {
    Route::get('/_test/exception', $throwException);

    $this->get('/_test/exception')->assertStatus($expectedStatus);

    Mail::assertNothingSent();
})->with([
    'missing file uploads trait' => [fn () => throw new MissingFileUploadsTraitException(new class
    {
        public function getName(): string
        {
            return 'auth.login';
        }
    }), 500],
    'too many requests' => [fn () => throw new TooManyRequestsHttpException(null, 'Too many invalid Livewire requests. Please try again later.'), 429],
]);

it('emails unexpected exceptions', function () {
    Route::get('/_test/exception', fn () => throw new RuntimeException('Boom'));

    $this->get('/_test/exception')->assertServerError();

    Mail::assertSent(ExceptionMail::class);
});
