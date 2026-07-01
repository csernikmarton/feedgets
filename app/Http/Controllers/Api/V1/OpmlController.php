<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImportOpmlRequest;
use App\Services\OpmlExportService;
use App\Services\OpmlImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OpmlController extends Controller
{
    public function import(ImportOpmlRequest $request, OpmlImportService $importer): JsonResponse
    {
        $contents = file_get_contents($request->file('file')->getRealPath());

        $result = $importer->import($contents, $request->user()->id);

        return response()->json([
            'message' => $result['message'],
            'count' => $result['count'],
        ], $result['success'] ? 200 : 422);
    }

    public function export(Request $request, OpmlExportService $exporter): Response
    {
        $opml = $exporter->export($request->user()->id);

        $filename = 'feedgets_subscriptions_'.date('Y-m-d').'.opml';

        return response($opml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
