<?php

use App\Events\PollingEvent;
use App\Http\Controllers\PollingController;
use App\Http\Controllers\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResources([
    'polling' => PollingController::class,
    'vote' => VoteController::class
]);

Route::controller(PollingController::class)->group(function () {
    Route::get('my-pollings', 'myPollings');
});

Route::get('tes-event', function () {
    broadcast(new PollingEvent(['status' => 200, 'message' => 'ada poll baru', 'data' => ['1', '2']]));
});

Route::prefix('auth')->group(function () {
    require __DIR__ . '/auth.php';
});