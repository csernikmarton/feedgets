<?php

namespace App\Services;

use App\Models\Feed;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class OpmlExportService
{
    /**
     * Export feeds to OPML format
     *
     * @param  int  $userId  User ID to export feeds for
     * @return string OPML XML content
     */
    public function export($userId)
    {
        try {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><opml version="1.0"></opml>');

            $head = $xml->addChild('head');
            $head->addChild('title', 'Feedgets Subscriptions Export');
            $head->addChild('dateCreated', date('r'));

            $body = $xml->addChild('body');

            $feeds = $this->getUserFeeds($userId);

            foreach ($feeds as $feed) {
                $outline = $body->addChild('outline');
                $outline->addAttribute('text', $feed->title);
                $outline->addAttribute('title', $feed->title);
                $outline->addAttribute('type', 'rss');
                $outline->addAttribute('xmlUrl', $feed->url);
                if ($feed->description) {
                    $outline->addAttribute('description', $feed->description);
                }
            }

            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());

            return $dom->saveXML();
        } catch (\Exception $e) {
            Log::error('OPML export error: '.$e->getMessage());
            throw $e;
        }
    }

    protected function getUserFeeds($userId)
    {
        return Feed::where('user_id', $userId)->orderBy('position')->get();
    }
}
