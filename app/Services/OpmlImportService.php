<?php

namespace App\Services;

use App\Models\Feed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleXMLElement;

use function Illuminate\Support\defer;

class OpmlImportService
{
    /**
     * Import feeds from an OPML file
     *
     * @param  string  $fileContents  Contents of the OPML file
     * @param  int  $userId  User ID to associate feeds with
     * @return array Result information
     */
    public function import($fileContents, $userId)
    {
        try {
            $xml = $this->loadAndValidateXml($fileContents);
            if (! $xml) {
                return [
                    'success' => false,
                    'message' => 'Invalid OPML file format.',
                    'count' => 0,
                ];
            }
        } catch (\Exception $e) {
            Log::error('OPML import error (Service 1): '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error processing OPML file: '.$e->getMessage(),
                'count' => 0,
            ];
        }

        try {
            $importCount = $this->parseAndImportFeeds($xml, $userId);

            if ($importCount > 0) {
                defer(function () use ($userId) {
                    Artisan::call('feeds:refresh', ['--user' => $userId]);
                });
            }

            return [
                'success' => true,
                'message' => 'OPML file imported successfully.',
                'count' => $importCount,
            ];
        } catch (\Exception $e) {
            Log::error('OPML import error (Service 2): '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error processing OPML file: '.$e->getMessage(),
                'count' => 0,
            ];
        }
    }

    /**
     * Load and validate the OPML XML file
     *
     * @param  string  $filePath  Path to the OPML file
     * @return SimpleXMLElement|false
     */
    private function loadAndValidateXml($filePath)
    {
        libxml_disable_entity_loader(true);

        $xml = simplexml_load_string($filePath);
        if (! $xml) {
            return false;
        }

        if (! isset($xml->body)) {
            return false;
        }

        return $xml;
    }

    /**
     * Parse the OPML XML and import feeds
     *
     * @param  SimpleXMLElement  $xml  OPML XML
     * @param  int  $userId  User ID to associate feeds with
     * @return int Number of feeds imported
     */
    protected function parseAndImportFeeds(SimpleXMLElement $xml, $userId)
    {
        $importCount = 0;
        $existingUrls = Feed::where('user_id', $userId)->pluck('url')->toArray();
        $maxPosition = Feed::where('user_id', $userId)->max('position') ?? 0;

        $importCount = $this->processOutlines($xml->body, $userId, $existingUrls, $maxPosition, $importCount);

        return $importCount;
    }

    /**
     * Process OPML outlines recursively
     *
     * @param  SimpleXMLElement  $element  Current XML element
     * @param  int  $userId  User ID to associate feeds with
     * @param  array  $existingUrls  URLs of existing feeds
     * @param  int  $maxPosition  Current maximum position
     * @param  int  $importCount  Running count of imported feeds
     * @return int Updated import count
     */
    private function processOutlines(SimpleXMLElement $element, $userId, $existingUrls, &$maxPosition, $importCount)
    {
        foreach ($element->outline as $outline) {
            $attributes = $outline->attributes();

            if (isset($attributes->xmlUrl) && ! empty($attributes->xmlUrl)) {
                $url = (string) $attributes->xmlUrl;

                if (in_array($url, $existingUrls)) {
                    continue;
                }

                $title = isset($attributes->title) ? (string) $attributes->title : '';
                $text = isset($attributes->text) ? (string) $attributes->text : '';
                $description = isset($attributes->description) ? (string) $attributes->description : '';

                $feedTitle = $title ?: $text ?: parse_url($url, PHP_URL_HOST) ?: 'Unnamed Feed';
                $feedDescription = $description ?: '';

                $maxPosition++;

                Feed::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $userId,
                    'title' => html_entity_decode($feedTitle),
                    'url' => $url,
                    'description' => html_entity_decode($feedDescription),
                    'position' => $maxPosition,
                ]);

                $importCount++;
                $existingUrls[] = $url;
            } elseif (count($outline->outline) > 0) {
                $importCount = $this->processOutlines($outline, $userId, $existingUrls, $maxPosition, $importCount);
            }
        }

        return $importCount;
    }
}
