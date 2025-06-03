<?php

namespace App\Livewire;

use App\Models\Feed;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.base', ['layout' => 'app'])]
#[Title('Dashboard')]
class FeedDashboard extends Component
{
    public function mount() {}

    public function render()
    {
        $feeds = Feed::where('user_id', Auth::id())->orderBy('position')->get();

        $columns = [
            collect(),
            collect(),
            collect(),
        ];

        foreach ($feeds as $feed) {
            $columnIndex = $feed->column !== null ? $feed->column : $feed->position % 3;

            $columnIndex = max(0, min(2, $columnIndex));

            $columns[$columnIndex]->push($feed);
        }

        return view('livewire.feed-dashboard', [
            'columns' => $columns,
        ]);
    }

    #[On('feeds-updated')]
    public function refreshFeedDisplay()
    {
        $this->dispatch('calculate-total-unread-count');
        $this->dispatch('refresh');
    }
}
