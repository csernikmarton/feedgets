<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\FeedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(key: 'uuid', keyType: 'string', incrementing: false)]
#[Fillable('uuid', 'user_id', 'title', 'url', 'description', 'position', 'column', 'oldest_published_at')]
class Feed extends Model
{
    /** @use HasFactory<FeedFactory> */
    use HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'oldest_published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function unreadArticles(): HasMany
    {
        return $this->articles()->where('is_read', false);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory(): FeedFactory
    {
        return FeedFactory::new();
    }
}
