<?php

use App\Http\Controllers\AuthLogin;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\GanadoController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\LecheController;
use App\Http\Controllers\NovillaAMontar;
use App\Http\Controllers\PartoController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\ToroController;
use App\Http\Controllers\TotalGanadoTipo;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacasEnGestacion;
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
    
    Route::apiResource('/toro',ToroController::class);
    Route::apiResource('/ganado',GanadoController::class);
    Route::apiResource('/insumo',InsumoController::class);
    Route::apiResource('/personal',PersonalController::class);
    Route::apiResource('/configuracion',ConfiguracionController::class)->only(['index','store','update']);
    Route::apiResource('/ganado/{ganado}/revision',RevisionController::class);
    Route::apiResource('/ganado/{ganado}/servicio',ServicioController::class);
    Route::apiResource('/ganado/{ganado}/parto',PartoController::class);
    Route::apiResource('/ganado/{ganado}/pesaje_leche',LecheController::class);
    Route::apiResource('usuario',UserController::class)->only(['show','update','destroy']);

   //rutas peticiones de datos dashboard
     Route::get('/total_ganado_tipo',TotalGanadoTipo::class);
     Route::get('/vacas_gestacion',VacasEnGestacion::class);
     Route::get('/cantidad_novillas_montar',[NovillaAMontar::class,'total']);
    
    //rutas peticiones datos para rellanr formularios
     Route::get('/novillas_montar',NovillaAMontar::class);
});
