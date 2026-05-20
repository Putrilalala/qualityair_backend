<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    protected $table = 'sensor_data_interval';

    protected $fillable = [
        'waktu',
        'temperature',
        'humidity',
        'gasMQ7',
        'gasMQ135'
    ];

    public $timestamps = false;
}