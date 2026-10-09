<?php

namespace App\Http\Controllers;

use App\Services\FaspayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaspayWebhookController extends Controller
{
    public function notify(Request $request, FaspayService $faspay): JsonResponse
    {
        $payload = $request->all();
        $raw = $request->getContent();
        if ($raw && str_contains($request->header('Content-Type', ''), 'xml')) {
            $xml = simplexml_load_string($raw);
            $payload = $xml ? json_decode(json_encode($xml), true) : [];
        }

        return response()->json($faspay->settleFromNotify($payload ?: []));
    }
}
