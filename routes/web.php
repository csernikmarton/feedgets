<?php

use App\Livewire\Contact;
use App\Livewire\Faq;
use App\Livewire\FeedDashboard;
use App\Livewire\ManageFeeds;
use App\Livewire\Settings\UserProfile;
use Illuminate\Support\Facades\Route;

Route::get('faq', Faq::class)->name('faq');
Route::get('contact', Contact::class)->name('contact');

Route::middleware(['auth'])->group(function () {
    Route::get('/', FeedDashboard::class)
        ->middleware(['verified'])
        ->name('dashboard');

    Route::get('feeds/manage', ManageFeeds::class)->name('feeds.manage');
    Route::get('profile', UserProfile::class)->name('profile');
});

require __DIR__.'/auth.php';
