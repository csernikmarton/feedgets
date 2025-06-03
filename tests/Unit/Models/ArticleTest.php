<?php

declare(strict_types=1);

use App\Models\Article;

test('article has uuid primary key', function () {
    $article = new Article;

    expect($article->getKeyName())->toBe('uuid')
        ->and($article->incrementing)->toBeFalse();
});

test('article has fillable attributes', function () {
    $fillable = [
        'uuid',
        'feed_uuid',
        'title',
        'description',
        'link',
        'guid',
        'published_at',
        'is_read',
    ];

    expect((new Article)->getFillable())->toBe($fillable);
});

test('article has correct casts', function () {
    $casts = [
        'published_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    expect((new Article)->getCasts())->toMatchArray($casts);
});

test('article belongs to a feed', function () {
    $article = new Article;
    $relation = $article->feed();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($relation->getRelated())->toBeInstanceOf(\App\Models\Feed::class);
});
