<?php

use App\Http\Controllers\AsignarNumeroCriaController;
use App\Http\Controllers\AuthLogin;
use App\Http\Controllers\BalanceAnualLeche;
use App\Http\Controllers\CaparCriaController;
use App\Http\Controllers\CompradorController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardFallecimientosController;
use App\Http\Controllers\DashboardVentaGanadoController;
use App\Http\Controllers\DashboardVentaLecheController;
use App\Http\Controllers\FallecimientoController;
use App\Http\Controllers\GanadoController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\LecheController;
use App\Http\Controllers\Logout;
use App\Http\Controllers\MayorCantidadInsumo;
use App\Http\Controllers\MenorCantidadInsumo;
use App\Http\Controllers\NovillaAMontar;
use App\Http\Controllers\PartoController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PrecioController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TodosPesajeLeche;
use App\Http\Controllers\TodosRevisiones;
use App\Http\Controllers\TodosServicios;
use App\Http\Controllers\TopVacasMenosProductoras;
use App\Http\Controllers\TopVacasProductoras;
use App\Http\Controllers\ToroController;
use App\Http\Controllers\TotalGanadoPendienteRevision;
use App\Http\Controllers\TotalGanadoTipo;
use App\Http\Controllers\TotalPersonal;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacasEnGestacion;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaLecheController;
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
Route::post('register',[UserController::class,'store'])->name('usuario.store');

Route::middleware('auth:sanctum')->group(function(){
    
    Route::get('/logout',Logout::class);

    Route::apiResource('/comprador',CompradorController::class);
    Route::apiResource('/ventas',VentaController::class);
    Route::apiResource('/toro',ToroController::class);
    Route::apiResource('/ganado',GanadoController::class);
    Route::apiResource('/insumo',InsumoController::class);
    Route::apiResource('/personal',PersonalController::class);
    Route::apiResource('/configuracion',ConfiguracionController::class)->only(['index','store','update']);
    Route::apiResource('/ganado/{ganado}/revision',RevisionController::class);
    Route::get('/revisiones',TodosRevisiones::class)->name('todasRevisiones');
    Route::get('/servicios',TodosServicios::class)->name('todasServicios');
    Route::get('/pesaje_leche',TodosPesajeLeche::class)->name('todosPesajesLeche');
    Route::apiResource('/ganado/{ganado}/servicio',ServicioController::class);
    Route::apiResource('/ganado/{ganado}/parto',PartoController::class);
    Route::apiResource('/ganado/{ganado}/pesaje_leche',LecheController::class);
    Route::apiResource('usuario',UserController::class)->only(['show','update','destroy'])->parameters(['usuario'=>'user']);
    Route::apiResource('/precio',PrecioController::class)->only(['index','store']);
    Route::apiResource('/venta_leche',VentaLecheController::class)->only(['index','store']);
   Route::apiResource('/fallecimientos',FallecimientoController::class);

    Route::get('/crias_pendiente_capar',[CaparCriaController::class,'index'])->name('capar.index');
    Route::get('/capar_cria/{ganado}',[CaparCriaController::class,'capar'])->name('capar.capar');
   
    Route::get('/crias_pendiente_numeracion',[AsignarNumeroCriaController::class,'index'])->name('numeracion.index');
    Route::post('/asignar_numero_cria/{ganado}',[AsignarNumeroCriaController::class,'store'])->name('numeracion.store');
   
    //rutas peticiones de datos dashboard
     Route::get('/total_ganado_tipo',TotalGanadoTipo::class);
     Route::get('/total_personal',TotalPersonal::class);
     Route::get('/vacas_gestacion',VacasEnGestacion::class);
     Route::get('/vacas_productoras',TopVacasProductoras::class);
     Route::get('/vacas_menos_productoras',TopVacasMenosProductoras::class);
     Route::get('/ganado_pendiente_revision',TotalGanadoPendienteRevision::class);
     Route::get('/cantidad_novillas_montar',[NovillaAMontar::class,'total']);
     Route::get('/menor_insumo',MenorCantidadInsumo::class);
     Route::get('/mayor_insumo',MayorCantidadInsumo::class);
     Route::get('/balance_anual_leche',BalanceAnualLeche::class);

    //rutas peticiones de datos dashboard venta leche
    Route::get('/dashboard_venta_leche/precio_actual',[DashboardVentaLecheController::class, 'precioActual'])->name('dashboardVentaLeche.precioActual');
    Route::get('/dashboard_venta_leche/variacion_precio',[DashboardVentaLecheController::class, 'variacionPrecio'])->name('dashboardVentaLeche.variacionPrecio');
    Route::get('/dashboard_venta_leche/ganancias_mes',[DashboardVentaLecheController::class, 'gananciasDelMes'])->name('dashboardVentaLeche.gananciasDelMes');
    Route::get('/dashboard_venta_leche/ventas_mes',[DashboardVentaLecheController::class, 'ventasDelMes'])->name('dashboardVentaLeche.ventasDelMes');
    
    //rutas peticiones de datos dashboard venta ganado
    Route::get('/dashboard_venta_ganado/mejor_comprador',[DashboardVentaGanadoController::class, 'mejorComprador'])->name('dashboardVentaGanado.mejorComprador');
    Route::get('/dashboard_venta_ganado/mejor_venta',[DashboardVentaGanadoController::class, 'mejorVenta'])->name('dashboardVentaGanado.mejorVenta');
    Route::get('/dashboard_venta_ganado/peor_venta',[DashboardVentaGanadoController::class, 'peorVenta'])->name('dashboardVentaGanado.peorVenta');
    Route::get('/dashboard_venta_ganado/ventas_mes',[DashboardVentaGanadoController::class, 'ventasDelMes'])->name('dashboardVentaGanado.ventasDelMes');

    //rutas peticiones de datos dashboard venta ganado
    Route::get('/dashboard_fallecimientos/causas_frecuentes', [DashboardFallecimientosController::class, 'causasMuertesFrecuentes'])->name('dashboardFallecimientos.causasMuertesFrecuentes');



    //rutas peticiones datos para rellanr formularios
     Route::get('/novillas_montar',NovillaAMontar::class);
});
