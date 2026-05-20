<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SensorData;
use Carbon\Carbon;

class SensorDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CLEAR existing data first to avoid mix-up
        SensorData::truncate();

        // Generate data for the last 7 days
        for ($day = 6; $day >= 0; $day--) {
            // Create 24 readings per day (1 per hour)
            for ($hour = 0; $hour < 24; $hour++) {
                $time = Carbon::now()->subDays($day)->setHour($hour)->setMinute(rand(0, 59));

                SensorData::create([
                    'temperature' => 25 + (rand(-30, 30) / 10.0),
                    'humidity' => 60 + (rand(-100, 100) / 10.0),
                    'mq7' => rand(50, 200) / 10.0,
                    'mq135' => rand(50, 200) / 10.0,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
            }
        }
    }
}
