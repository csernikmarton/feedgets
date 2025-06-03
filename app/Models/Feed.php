<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\FeedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    /** @use HasFactory<FeedFactory> */
    use HasFactory, HasUuid;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'url',
        'description',
        'position',
        'column',
        'oldest_published_at',
    ];

    protected $casts = [
        'oldest_published_at' => 'datetime',
    ];

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
