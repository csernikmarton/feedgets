<?php

use App\Livewire\FeedDashboard;
use App\Livewire\ManageFeeds;
use App\Livewire\Settings\UserProfile;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('faq', App\Livewire\Faq::class)->name('faq');
Route::get('contact', App\Livewire\Contact::class)->name('contact');

Route::middleware(['auth'])->group(function () {
    Route::get('/', FeedDashboard::class)
        ->middleware(['verified'])
        ->name('dashboard');

    Route::get('feeds/manage', ManageFeeds::class)->name('feeds.manage');

    Route::get('profile', UserProfile::class)->name('profile');
});

require __DIR__.'/auth.php';
