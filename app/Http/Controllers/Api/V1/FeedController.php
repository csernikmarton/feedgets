<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderFeedRequest;
use App\Http\Requests\Api\V1\StoreFeedRequest;
use App\Http\Requests\Api\V1\UpdateFeedRequest;
use App\Http\Resources\Api\V1\FeedResource;
use App\Models\Feed;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function __construct(private readonly FeedService $feeds) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $feeds = $request->user()->feeds()
            ->withCount(['unreadArticles as unread_articles_count'])
            ->orderBy('column')
            ->orderBy('position')
            ->get();

        return FeedResource::collection($feeds);
    }

    public function store(StoreFeedRequest $request): JsonResponse
    {
        $feed = $this->feeds->createFeed($request->user(), $request->validated());

        return (new FeedResource($feed))->response()->setStatusCode(201);
    }

    public function show(Request $request, Feed $feed): FeedResource
    {
        $this->authorize('view', $feed);

        $feed->loadCount(['unreadArticles as unread_articles_count']);

        return new FeedResource($feed);
    }

    public function update(UpdateFeedRequest $request, Feed $feed): FeedResource
    {
        $this->authorize('update', $feed);

        $feed = $this->feeds->updateFeed($feed, $request->validated());

        return new FeedResource($feed);
    }

    public function destroy(Request $request, Feed $feed): JsonResponse
    {
        $this->authorize('delete', $feed);

        $feed->delete();

        return response()->json(status: 204);
    }

    public function reorder(ReorderFeedRequest $request, Feed $feed): FeedResource
    {
        $this->authorize('update', $feed);

        $this->feeds->reorder($feed, $request->integer('column'), $request->integer('index'));

        return new FeedResource($feed->refresh());
    }
}
