<?php

use App\Http\Controllers\AuthLogin;
use App\Http\Controllers\GanadoController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('login',AuthLogin::class);
Route::post('register',[UserController::class,'store']);

Route::middleware('auth:sanctum')->group(function(){

    Route::apiResource('/ganado',GanadoController::class);
    Route::apiResource('usuario',UserController::class)->only(['show','update','destroy']);

});
