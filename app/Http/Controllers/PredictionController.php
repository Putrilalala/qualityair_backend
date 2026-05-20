<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PredictionController extends Controller
{
    /**
     * =========================================================
     * GET ALL ACTIVE PREDICTIONS
     * =========================================================
     * Endpoint:
     * /api/predictions
     */

    public function index(): JsonResponse
    {
        $latestBatch = DB::table('predictions')
            ->max('created_at');

        if (!$latestBatch) {
            return response()->json([
                'status' => 'error',
                'message' => 'No prediction data found'
            ], 404);
        }

        $now = Carbon::now();

        $predictions = DB::table('predictions')
            ->select([
                'waktu',
                'temperature',
                'humidity',
                'gasMQ7',
                'gasMQ135',
                'aqi_label',
                'aqi_color'
            ])
            ->where('created_at', $latestBatch)
            ->where('waktu', '>=', $now)
            ->orderBy('waktu', 'asc')
            ->limit(288)
            ->get();

        return response()->json([
            'status' => 'success',
            'server_time' => $now,
            'prediction_batch' => $latestBatch,
            'count' => $predictions->count(),
            'data' => $predictions
        ]);
    }

    /**
     * =========================================================
     * GET NEAREST PREDICTION
     * =========================================================
     * Endpoint:
     * /api/predictions/latest
     */

    public function latest(): JsonResponse
    {
        $latestBatch = DB::table('predictions')
            ->max('created_at');

        if (!$latestBatch) {
            return response()->json([
                'status' => 'error',
                'message' => 'No prediction data found'
            ], 404);
        }

        $now = Carbon::now();

        $prediction = DB::table('predictions')
            ->select([
                'waktu',
                'temperature',
                'humidity',
                'gasMQ7',
                'gasMQ135',
                'aqi_label',
                'aqi_color'
            ])
            ->where('created_at', $latestBatch)
            ->where('waktu', '>=', $now)
            ->orderBy('waktu', 'asc')
            ->first();

        return response()->json([
            'status' => 'success',
            'server_time' => $now,
            'data' => $prediction
        ]);
    }

    /**
     * =========================================================
     * GET PREDICTION HISTORY
     * =========================================================
     * Endpoint:
     * /api/predictions/history
     */

    public function history(): JsonResponse
    {
        $history = DB::table('predictions')
            ->select([
                'waktu',
                'temperature',
                'humidity',
                'gasMQ7',
                'gasMQ135',
                'aqi_label'
            ])
            ->orderBy('waktu', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $history->count(),
            'data' => $history
        ]);
    }
}