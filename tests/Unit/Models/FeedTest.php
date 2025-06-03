<?php

declare(strict_types=1);

use App\Models\Feed;

test('feed has uuid primary key', function () {
    $feed = new Feed;

    expect($feed->getKeyName())->toBe('uuid')
        ->and($feed->incrementing)->toBeFalse();
});

test('feed has fillable attributes', function () {
    $fillable = [
        'uuid',
        'user_id',
        'title',
        'url',
        'description',
        'position',
        'column',
        'oldest_published_at',
    ];

    expect((new Feed)->getFillable())->toBe($fillable);
});

test('feed has correct casts', function () {
    $casts = [
        'oldest_published_at' => 'datetime',
    ];

    expect((new Feed)->getCasts())->toMatchArray($casts);
});

test('feed belongs to a user', function () {
    $feed = new Feed;
    $relation = $feed->user();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($relation->getRelated())->toBeInstanceOf(\App\Models\User::class);
});

test('feed has many articles', function () {
    $feed = new Feed;
    $relation = $feed->articles();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($relation->getRelated())->toBeInstanceOf(\App\Models\Article::class);
});

test('feed has many unread articles', function () {
    $feed = new Feed;
    $relation = $feed->unreadArticles();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($relation->getRelated())->toBeInstanceOf(\App\Models\Article::class);

    // Check that the query includes the WHERE clause for is_read = false
    $sql = $relation->toSql();
    expect($sql)->toContain('"is_read" = ?');

    // Check that the binding for is_read is false
    $bindings = $relation->getBindings();
    expect($bindings)->toContain(false);
});
