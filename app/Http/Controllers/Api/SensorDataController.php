<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SensorData;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SensorDataController extends Controller
{
    // Store new sensor data via API (non-DB for quick test)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'suhu' => 'required|numeric',
            'kelembapan' => 'required|numeric',
            'mq7_ppm' => 'required|numeric',
            'mq135_ppm' => 'required|numeric',
        ]);

        $data = [
            'timestamp' => now()->toDateTimeString(),
            'suhu' => $validated['suhu'],
            'kelembapan' => $validated['kelembapan'],
            'mq7_ppm' => $validated['mq7_ppm'],
            'mq135_ppm' => $validated['mq135_ppm'],
        ];

        $latestPath = storage_path('app/mqtt-latest.json');
        $historyPath = storage_path('app/mqtt-history.json');

        file_put_contents($latestPath, json_encode($data));

        $history = [];
        if (file_exists($historyPath)) {
            $history = json_decode(file_get_contents($historyPath), true) ?: [];
        }
        array_unshift($history, $data);
        $history = array_slice($history, 0, 100);
        file_put_contents($historyPath, json_encode($history));

        return response()->json($data, 201);
    }

    // Get latest reading from file cache
    public function latest()
    {
        $latestPath = storage_path('app/mqtt-latest.json');
        if (!file_exists($latestPath)) {
            return response()->json(null, 204);
        }

        $payload = json_decode(file_get_contents($latestPath), true);
        return response()->json($payload);
    }

    // Get history for charts (last 50 readings) from file cache
    public function history()
    {
        $historyPath = storage_path('app/mqtt-history.json');
        if (!file_exists($historyPath)) {
            return response()->json([], 204);
        }

        $history = json_decode(file_get_contents($historyPath), true) ?: [];
        return response()->json(array_slice($history, 0, 50));
    }

    // Predict future quality
    public function predict()
    {
        $historyPath = storage_path('app/mqtt-history.json');
        if (!file_exists($historyPath)) {
            return response()->json(['error' => 'No data for prediction'], 400);
        }

        $history = json_decode(file_get_contents($historyPath), true) ?: [];
        $data = collect(array_slice($history, 0, 100))->map(function ($item) {
            return [
                'timestamp' => strtotime($item['timestamp']),
                'mq135_ppm' => $item['mq135_ppm'],
                'suhu' => $item['suhu'],
            ];
        })->values();

        if ($data->count() < 5) {
            return response()->json(['error' => 'Not enough data for prediction (need at least 5)'], 400);
        }

        $jsonInput = $data->toJson();
        $scriptPath = base_path('ml/predict.py');

        $process = new Process(['python3', $scriptPath]);
        $process->setInput($jsonInput);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return response()->json(json_decode($process->getOutput()));
    }

    private function formatOutput(SensorData $item)
    {
        return [
            'suhu' => $item->temperature,
            'kelembapan' => $item->humidity,
            'mq7_ppm' => $item->mq7,
            'mq135_ppm' => $item->mq135,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}
