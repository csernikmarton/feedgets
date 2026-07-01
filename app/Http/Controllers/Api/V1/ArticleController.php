<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArticleResource;
use App\Models\Article;
use App\Models\Feed;
use App\Services\FeedService;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function __construct(private readonly FeedService $feeds) {}

    /**
     * List articles, either across all of the user's feeds or scoped to one feed.
     */
    #[QueryParameter('unread', description: 'Only return unread articles.', type: 'boolean')]
    #[QueryParameter('per_page', description: 'Items per page (1–100).', type: 'integer', default: 30)]
    public function index(Request $request, ?Feed $feed = null): AnonymousResourceCollection
    {
        if ($feed) {
            $this->authorize('view', $feed);
            $query = $feed->articles();
        } else {
            $query = Article::whereIn('feed_uuid', $request->user()->feeds()->select('uuid'));
        }

        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $perPage = min($request->integer('per_page', 30), 100);

        $articles = $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ArticleResource::collection($articles);
    }

    public function show(Request $request, Article $article): ArticleResource
    {
        $this->authorize('view', $article->feed);

        return new ArticleResource($article);
    }

    public function markAsRead(Request $request, Article $article): ArticleResource
    {
        $this->authorize('view', $article->feed);

        $article->markAsRead();

        return new ArticleResource($article);
    }

    public function markAllAsRead(Request $request, Feed $feed): JsonResponse
    {
        $this->authorize('view', $feed);

        $publishedAt = $request->date('published_at') ?? now();

        $this->feeds->markAllAsRead($feed, $publishedAt->toDateTimeString());

        return response()->json([
            'unread_count' => $feed->unreadArticles()->count(),
        ]);
    }
}
