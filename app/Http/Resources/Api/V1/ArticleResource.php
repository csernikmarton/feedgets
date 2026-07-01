<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Article
 */
class ArticleResource extends JsonResource
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
            'feed_uuid' => $this->feed_uuid,
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'guid' => $this->guid,
            'published_at' => $this->published_at,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at,
        ];
    }
}
