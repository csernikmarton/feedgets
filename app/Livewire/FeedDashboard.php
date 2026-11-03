<?php

namespace App\Livewire;

use App\Models\Feed;
use App\Services\FeedService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.base', ['layout' => 'app'])]
#[Title('Dashboard')]
class FeedDashboard extends Component
{
    public $isRefreshing = false;

    public function mount() {}

    public function isRefreshing(): bool
    {
        return $this->isRefreshing;
    }

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

    #[On('update-position')]
    public function updatePosition($feedId, $fromColumn, $toColumn, $newIndex)
    {
        $this->skipRender();

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
            $this->dispatch('position-update-failed', errors: $validator->errors()->toArray());

            return;
        }

        $validated = $validator->validated();

        $feed = Feed::findOrFail($validated['feedId']);

        if ($feed->user_id !== Auth::id()) {
            session()->flash('error', 'Unauthorized action');
            $this->redirectRoute('dashboard');

            return;
        }

        try {
            app(FeedService::class)->reorder($feed, $validated['toColumn'], $validated['newIndex']);

            $this->dispatch('position-updated');
        } catch (\Exception $e) {
            Log::error(__('Error updating feed position').': '.$e->getMessage(), [
                'exception' => $e,
                'data' => $data,
            ]);

            $this->dispatch('position-update-failed', message: __('Error updating feed position'));
        }
    }
}
