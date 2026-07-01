<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Feed
 */
class FeedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'position' => $this->position,
            'column' => $this->column,
            'unread_count' => $this->when(
                $this->unread_articles_count !== null,
                fn () => (int) $this->unread_articles_count,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
