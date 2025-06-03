<?php

namespace App\Livewire;

use App\Livewire\Forms\FeedForm;
use App\Models\Feed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.base', ['layout' => 'app'])]
#[Title('Manage Feeds')]
class ManageFeeds extends Component
{
    use WithFileUploads;

    public FeedForm $form;

    public ?string $editingFeedId = null;

    public $opmlFile;

    public function render()
    {
        return view('livewire.manage-feeds', [
            'feeds' => Feed::where('user_id', Auth::id())->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        $this->editingFeedId = null;
        $this->form->reset();
    }

    public function edit(Feed $feed)
    {
        if ($feed->user_id !== Auth::id()) {
            session()->flash('message', __('You do not have permission to edit this feed.'));

            return;
        }

        $this->editingFeedId = $feed->uuid;
        $this->form->setFeed($feed);
    }

    public function save()
    {
        if ($this->editingFeedId) {
            $feed = Feed::findOrFail($this->editingFeedId);

            if ($feed->user_id !== Auth::id()) {
                session()->flash('message', __('You do not have permission to update this feed.'));

                return;
            }

            $this->form->update();
            session()->flash('message', __('Feed updated successfully.'));
        } elseif (Feed::where('url', $this->form->url)->where('user_id', Auth::id())->exists()) {
            session()->flash('message', __('This feed has already been added.'));
        } else {
            $this->form->store();
            session()->flash('message', __('Feed added successfully.'));
        }

        $this->dispatch('feeds-updated');
        $this->reset('editingFeedId');
        $this->form->reset();
    }

    public function delete(Feed $feed)
    {
        if ($feed->user_id !== Auth::id()) {
            session()->flash('message', __('You do not have permission to delete this feed.'));

            return;
        }

        $feed->delete();
        session()->flash('message', __('Feed deleted successfully.'));
        $this->dispatch('feeds-updated');

        if ($this->editingFeedId === (string) $feed->uuid) {
            $this->reset('editingFeedId');
            $this->form->reset();
        }
    }

    public function importOpml()
    {
        $this->validate([
            'opmlFile' => 'required|file|mimes:xml,opml|max:2048',
        ]);

        try {
            $fileContents = file_get_contents($this->opmlFile->getRealPath());

            if (! $fileContents) {
                throw new \Exception(__('Could not read OPML file contents'));
            }

            $importer = app(\App\Services\OpmlImportService::class);
            $result = $importer->import($fileContents, Auth::id());

            if ($result['success']) {
                session()->flash('message', __("Successfully imported {$result['count']} feed(s) from OPML file."));
                $this->reset('opmlFile');
                $this->dispatch('feeds-updated');
                $this->dispatch('close-modal', 'import-opml-modal');
            } else {
                session()->flash('error', __('Failed to import OPML file: '.$result['message']));
            }
        } catch (\Exception $e) {
            Log::error(__('OPML import error').': '.$e->getMessage());
            session()->flash('error', __('An error occurred while importing the OPML file').': '.$e->getMessage());
        }
    }

    public function exportOpml()
    {
        try {
            $exporter = app(\App\Services\OpmlExportService::class);
            $opmlContent = $exporter->export(Auth::id());

            $filename = 'feedgets_subscriptions_'.date('Y-m-d').'.opml';

            return response()->streamDownload(function () use ($opmlContent) {
                echo $opmlContent;
            }, $filename, [
                'Content-Type' => 'text/xml',
            ]);
        } catch (\Exception $e) {
            Log::error(__('OPML export error').': '.$e->getMessage());
            session()->flash('error', __('An error occurred while exporting your feeds').': '.$e->getMessage());
        }
    }
}
