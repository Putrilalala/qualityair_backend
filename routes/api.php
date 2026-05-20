<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SensorController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\Api\SensorDataController;

/*
|--------------------------------------------------------------------------
| SENSOR API
|--------------------------------------------------------------------------
*/

Route::prefix('sensors')->group(function () {

    // realtime latest sensor
    Route::get('/latest', [SensorDataController::class, 'latest']);

    // historical sensor
    Route::get('/history', [SensorDataController::class, 'history']);

    // prediction from ML
    Route::get('/predict', [SensorDataController::class, 'predict']);
});

/*
|--------------------------------------------------------------------------
| AGGREGATION API
|--------------------------------------------------------------------------
*/

Route::get('/daily', [SensorController::class, 'daily']);

Route::get('/hourly', [SensorController::class, 'hourly']);

Route::get('/latest', [SensorController::class, 'latest']);

/*
|--------------------------------------------------------------------------
| PREDICTION API
|--------------------------------------------------------------------------
*/

Route::prefix('predictions')->group(function () {

    // all prediction chart
    Route::get('/', [PredictionController::class, 'index']);

    // latest prediction
    Route::get('/latest', [PredictionController::class, 'latest']);

    // prediction history
    Route::get('/history', [PredictionController::class, 'history']);
});

/*
|--------------------------------------------------------------------------
| MANUAL ML RUN (OPTIONAL)
|--------------------------------------------------------------------------
*/

Route::get('/run-predict', [PredictionController::class, 'runPredict']);