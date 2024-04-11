<?php

use App\Http\Controllers\ReportsPdfController;
use App\Http\Controllers\BackupRestoreBDController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//rutas reportes pdf
Route::get('/reportes/ganado/{ganado}', [ReportsPdfController::class, 'resumenGanado'])->name('reportes.ganado');
Route::get('/reportes/general', [ReportsPdfController::class, 'resumenGeneral'])->name('reportes.general');
Route::get('/reportes/venta_leche', [ReportsPdfController::class, 'resumenVentaLecheMesActual'])->name('reportes.ventaLeche');
Route::get('/reportes/venta_ganado', [ReportsPdfController::class, 'resumenVentaGanadoAnual'])->name('reportes.ventaGanado');
Route::get('/reportes/causas_fallecimientos', [ReportsPdfController::class, 'resumenCausasFAllecimientos'])->name('reportes.fallecimientos');
Route::get('/respaldo',[BackupRestoreBDController::class,'respaldarBd']);
Route::get('/restaurar', [BackupRestoreBDController::class, 'restaurarBd']);
