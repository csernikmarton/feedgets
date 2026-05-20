<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(key: 'uuid', keyType: 'string', incrementing: false)]
#[Fillable('uuid', 'feed_uuid', 'title', 'description', 'link', 'guid', 'published_at', 'is_read')]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasUuid;


    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_read' => 'boolean',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
