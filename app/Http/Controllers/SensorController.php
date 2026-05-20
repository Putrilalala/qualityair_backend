<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    // ===============================
    // 🔴 DATA TERBARU
    // ===============================
    public function latest()
    {
        $data = SensorData::orderBy('waktu', 'desc')->first();

        if (!$data) {
            return response()->json([
                'message' => 'No data available'
            ], 404);
        }

        return response()->json([
            'suhu'       => $data->temperature,
            'kelembapan' => $data->humidity,
            'mq7_ppm'    => $data->gasMQ7,
            'mq135_ppm'  => $data->gasMQ135,
            'waktu'      => $data->waktu,
        ]);
    }

    // ===============================
    // 📊 DAILY (7 hari terakhir)
    // Ambil data dari sensor_data_daily untuk hari sebelumnya,
    // lalu selalu hitung ulang nilai hari ini dari interval untuk akurasi realtime.
    // ===============================
    public function daily()
    {
        $today = Carbon::now()->toDateString();
        $sevenDaysAgo = Carbon::now()->subDays(6)->toDateString();

        // 1. Ambil data 7 hari kecuali hari ini dari table daily
        $dailyData = DB::table('sensor_data_daily')
            ->select('tanggal', 'temperature', 'humidity', 'gasMQ7', 'gasMQ135')
            ->where('tanggal', '>=', $sevenDaysAgo)
            ->where('tanggal', '<', $today)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->keyBy('tanggal');

        // 2. Hitung ulang hari ini dari sensor_data_interval agar tidak pakai nilai stale
        $todayData = DB::table('sensor_data_interval')
            ->selectRaw('
                DATE(waktu) as tanggal,
                ROUND(AVG(temperature),2) as temperature,
                ROUND(AVG(humidity),2) as humidity,
                ROUND(AVG(gasMQ7),2) as gasMQ7,
                ROUND(AVG(gasMQ135),2) as gasMQ135
            ')
            ->whereDate('waktu', $today)
            ->groupByRaw('DATE(waktu)')
            ->first();

        // 3. Jika tidak ada data interval hari ini, gunakan row hari ini dari daily jika tersedia
        if (!$todayData) {
            $todayData = DB::table('sensor_data_daily')
                ->select('tanggal', 'temperature', 'humidity', 'gasMQ7', 'gasMQ135')
                ->where('tanggal', $today)
                ->first();
        }

        if ($todayData) {
            $dailyData[$today] = $todayData;
        }

        return response()->json(
            collect($dailyData)->values()->map(function ($d) {
                return [
                    'date'       => $d->tanggal,
                    'day'        => date('D', strtotime($d->tanggal)),
                    'suhu'       => round($d->temperature, 1),
                    'kelembapan' => (int) round($d->humidity),
                    'mq7_ppm'    => (int) round($d->gasMQ7),
                    'mq135_ppm'  => (int) round($d->gasMQ135),
                ];
            })
        );
    }
    // ===============================
    // ⏱️ HOURLY (per jam)
    // Ambil langsung dari sensor_data_hourly
    // ===============================
    public function hourly(Request $request)
    {
        $date = $request->query('date'); // opsional: filter hari tertentu

        $query = DB::table('sensor_data_hourly')
            ->select('waktu', 'temperature', 'humidity', 'gasMQ7', 'gasMQ135');

        if ($date) {
            // Filter berdasarkan tanggal tertentu
            $query->whereDate('waktu', $date);
        } else {
            // Default: 2 hari terakhir
            $query->where('waktu', '>=', Carbon::now()->subDays(7));
        }

        $data = $query->orderBy('waktu', 'asc')->get();

        return response()->json(
            $data->map(function ($d) {
                return [
                    'date'       => Carbon::parse($d->waktu)->format('Y-m-d'),
                    'time'       => Carbon::parse($d->waktu)->format('H:i'),
                    'day'        => Carbon::parse($d->waktu)->format('D'),
                    'suhu'       => round($d->temperature, 1),
                    'kelembapan' => (int) round($d->humidity),
                    'mq7_ppm'   => (int) round($d->gasMQ7),
                    'mq135_ppm' => (int) round($d->gasMQ135),
                ];
            })->values()
        );
    }
}