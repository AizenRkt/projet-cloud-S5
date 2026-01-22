<?php

namespace App\Http\Controllers;

use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function sync(): JsonResponse
    {
        $timestamp = now();

        Log::info('Manual sync triggered', [
            'synced_at' => $timestamp->toIso8601String(),
            'signalements' => Signalement::count(),
        ]);

        return response()->json([
            'message' => 'Synchronisation simulée avec succès.',
            'synced_at' => $timestamp->toDateTimeString(),
            'processed_records' => Signalement::count(),
        ]);
    }
}
