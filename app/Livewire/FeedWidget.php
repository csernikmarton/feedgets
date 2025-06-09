<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class FeedWidget extends Component
{
    public Feed $feed;

    public $unreadCount = 0;

    public $lastRefreshTime;

    public $isRefreshing = false;

    public $page = 1;

    public $perPage = 30;

    public $articles = [];

    public $hasMoreArticles = true;

    public $isLoading = false;

    public $totalArticlesLoaded = 0;

    public function mount(Feed $feed)
    {
        $this->feed = $feed;
        $this->refreshUnreadCount();
        $this->lastRefreshTime = now();

        if (empty($this->articles) || count($this->articles) == 0) {
            $this->articles = $this->getArticles();
            $this->totalArticlesLoaded = $this->articles->count();

            $this->hasMoreArticles = $this->articles->count() == $this->perPage;

            $totalArticlesInDb = $this->feed->articles()->count();
            if ($totalArticlesInDb > $this->totalArticlesLoaded) {
                $this->hasMoreArticles = true;
            }
        }
    }

    public function render()
    {
        return view('livewire.feed-widget', [
        ]);
    }

    public function refreshUnreadCount()
    {
        $this->unreadCount = $this->feed->unreadArticles()->count();
    }

    public function decrementUnreadCount()
    {
        $this->unreadCount--;
    }

    public function markArticleAsRead(string $articleId)
    {
        $article = Article::find($articleId);
        if ($article && ! $article->is_read) {
            $article->markAsRead();
            $this->decrementUnreadCount();

            // Notify parent component, ensure the event is processed
            // after the current request completes
            $this->dispatch('article-read', ['feed_uuid' => $article->feed_uuid]);
            $this->dispatch('decrement-total-unread-count');
        }
    }

    public function markAllAsRead($publishedAt)
    {
        if ($this->unreadCount > 0) {
            $this->isRefreshing = true;

            $this->feed->articles()
                ->where('is_read', false)
                ->where('published_at', '<=', $publishedAt)
                ->update(['is_read' => true]);

            $this->refresh();

            $this->dispatch('refresh-complete');
        }
    }

    #[On('article-read')]
    public function handleArticleRead($data = [])
    {
        $this->refreshUnreadCount();
    }

    public function refresh()
    {
        $this->isRefreshing = true;
        $this->lastRefreshTime = now();
        $this->refreshUnreadCount();

        $articlesToLoad = max($this->totalArticlesLoaded, $this->perPage);
        $pagesNeeded = (int) ceil($articlesToLoad / $this->perPage);

        $this->articles = $this->feed->articles()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->take($articlesToLoad)
            ->get();

        $this->page = $pagesNeeded;
        $this->hasMoreArticles = $this->articles->count() == $articlesToLoad;
        $this->totalArticlesLoaded = $this->articles->count();

        $this->dispatch('calculate-total-unread-count');
    }

    #[On('refresh-complete')]
    public function handleRefreshComplete()
    {
        $this->isRefreshing = false;
        $this->totalArticlesLoaded = $this->articles->count();
    }

    public function updatedPerPage()
    {
        $this->page = 1;

        $this->articles = $this->getArticles();
        $this->totalArticlesLoaded = $this->articles->count();

        $this->hasMoreArticles = $this->articles->count() == $this->perPage;

        $totalArticlesInDb = $this->feed->articles()->count();
        if ($totalArticlesInDb > $this->totalArticlesLoaded) {
            $this->hasMoreArticles = true;
        }
    }

    /**
     * Load more articles when user scrolls to the bottom
     */
    public function loadMoreArticles()
    {
        if (! $this->hasMoreArticles || $this->isLoading) {
            return;
        }

        $this->isLoading = true;

        $this->page++;

        $newArticles = $this->getArticles();

        if ($newArticles->count() > 0) {
            // Merge new articles with existing ones
            $this->articles = $this->articles->concat($newArticles);
            $this->totalArticlesLoaded = $this->articles->count();
        }

        $this->hasMoreArticles = $newArticles->count() == $this->perPage;

        $totalArticlesInDb = $this->feed->articles()->count();
        if ($this->totalArticlesLoaded >= $totalArticlesInDb) {
            $this->hasMoreArticles = false;
        }

        $this->isLoading = false;
    }

    public function getArticles(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->feed->articles()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();
    }

    #[On('update-position')]
    public function updatePosition($feedId, $fromColumn, $toColumn, $newIndex)
    {
        $data = [
            'feedId' => (string) $feedId,
            'fromColumn' => (int) $fromColumn,
            'toColumn' => (int) $toColumn,
            'newIndex' => (int) $newIndex,
        ];

        $validator = validator($data, [
            'feedId' => 'required|uuid|exists:feeds,uuid',
            'fromColumn' => 'required|integer|min:0|max:2',
            'toColumn' => 'required|integer|min:0|max:2',
            'newIndex' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $feed = Feed::findOrFail($validated['feedId']);

        if ($feed->user_id !== Auth::id()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action');
        }

        try {
            DB::beginTransaction();

            $feed->column = $validated['toColumn'];
            $feed->save();

            $feedsByColumn = [];
            for ($i = 0; $i <= 2; $i++) {
                $feedsByColumn[$i] = Feed::where('user_id', Auth::id())
                    ->where('column', $i)
                    ->whereNot('uuid', $validated['feedId'])
                    ->orderBy('position')
                    ->get();
            }

            $targetColumn = $feedsByColumn[$validated['toColumn']];
            $targetColumn = $targetColumn->slice(0, $validated['newIndex'])
                ->push($feed)
                ->concat($targetColumn->slice($validated['newIndex']));
            $feedsByColumn[$validated['toColumn']] = $targetColumn;

            $position = 1;
            for ($i = 0; $i <= 2; $i++) {
                foreach ($feedsByColumn[$i] as $feedItem) {
                    if ($feedItem->position !== $position) {
                        $feedItem->update(['position' => $position]);
                    }
                    $position++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Feed position updated successfully'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(__('Error updating feed position').': '.$e->getMessage(), [
                'exception' => $e,
                'data' => $data,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Error updating feed position'),
            ], 500);
        }
    }
}
