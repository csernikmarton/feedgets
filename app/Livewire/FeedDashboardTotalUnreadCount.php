<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class FeedDashboardTotalUnreadCount extends Component
{
    public $totalUnreadCount = 0;

    public function mount()
    {
        $this->calculateTotalUnreadCount();
    }

    public function render()
    {
        return view('livewire.feed-dashboard-total-unread-count');
    }

    #[On('calculate-total-unread-count')]
    public function calculateTotalUnreadCount()
    {
        $this->totalUnreadCount = Article::join('feeds', 'articles.feed_uuid', '=', 'feeds.uuid')
            ->where('feeds.user_id', Auth::id())
            ->where('articles.is_read', false)
            ->count();
    }

    #[On('decrement-total-unread-count')]
    public function decrementTotalUnreadCount()
    {
        $this->totalUnreadCount--;
    }

    public function markAllArticlesAsRead()
    {
        $feedIds = Feed::where('user_id', Auth::id())->pluck('uuid');

        Article::whereIn('feed_uuid', $feedIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->totalUnreadCount = 0;

        $this->dispatch('article-read');
        $this->dispatch('calculate-total-unread-count');

        session()->flash('message', 'All articles marked as read');
    }
}
