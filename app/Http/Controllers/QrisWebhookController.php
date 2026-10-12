<?php

namespace App\Http\Controllers;

use App\Services\QrisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrisWebhookController extends Controller
{
    public function notify(Request $request, QrisService $qris): JsonResponse
    {
        return $qris->settleFromWebhook($request);
    }
}
