<?php

use App\Http\Controllers\AsignarNumeroCriaController;
use App\Http\Controllers\AuthLogin;
use App\Http\Controllers\BackupRestoreBDController;
use App\Http\Controllers\CaparCriaController;
use App\Http\Controllers\checkSesionActivaUsuario;
use App\Http\Controllers\CompradorController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardFallecimientosController;
use App\Http\Controllers\DashboardJornadasVacunacion;
use App\Http\Controllers\DashboardPrincipalController;
use App\Http\Controllers\DashboardVentaGanadoController;
use App\Http\Controllers\DashboardVentaLecheController;
use App\Http\Controllers\DatosParaFormulariosController;
use App\Http\Controllers\FallecimientoController;
use App\Http\Controllers\FincaController;
use App\Http\Controllers\GanadoController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\LecheController;
use App\Http\Controllers\Logout;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PartoController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PrecioController;
use App\Http\Controllers\GanadoDescarteController;
use App\Http\Controllers\JornadaVacunacionController;
use App\Http\Controllers\PajuelaToroController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TodosPartos;
use App\Http\Controllers\TodosPesajeLeche;
use App\Http\Controllers\TodosRevisiones;
use App\Http\Controllers\TodosServicios;
use App\Http\Controllers\ToroController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsuarioVeterinarioController;
use App\Http\Controllers\VacunaController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\VentaLecheController;
use App\Models\Notificacion;
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

    Route::apiResource('usuarios_veterinarios',UsuarioVeterinarioController::class)->only(['index','store','update','destroy']);
    Route::apiResource('/comprador',CompradorController::class);
    Route::apiResource('/ventas',VentaController::class);
    Route::apiResource('/toro',ToroController::class);
    Route::apiResource('/ganado',GanadoController::class);
    Route::apiResource('vacunas',VacunaController::class);
    Route::apiResource('/ganado_descarte',GanadoDescarteController::class);
    Route::post('/descartar_ganado',[GanadoDescarteController::class,'descartar'])->name('ganado_descarte.descartar');
    //Route::apiResource('/insumo',InsumoController::class);
    Route::apiResource('/personal',PersonalController::class);
    Route::apiResource('/configuracion',ConfiguracionController::class)->only(['index','store','update']);
    Route::apiResource('/ganado/{ganado}/revision',RevisionController::class);
    Route::apiResource('/ganado/{ganado}/servicio',ServicioController::class);
    Route::apiResource('/ganado/{ganado}/parto',PartoController::class);
    Route::apiResource('/ganado/{ganado}/pesaje_leche',LecheController::class);
    Route::apiResource('usuario',UserController::class)->only(['show','update','destroy'])->parameters(['usuario'=>'user']);
    //Route::apiResource('/precio',PrecioController::class)->only(['index','store']);
/*  Route::apiResource('/venta_leche',VentaLecheController::class)->only(['index','store']);*/
   Route::apiResource('/pajuela_toros',PajuelaToroController::class);
   Route::apiResource('/fallecimientos',FallecimientoController::class);
   Route::apiResource('/jornada_vacunacion',JornadaVacunacionController::class);
   Route::apiResource('/notificaciones',NotificacionController::class)->only(['index','destroy'])->parameters(['notificaciones' => 'notificacion']);;
   Route::get('/borrar_notificaciones',[NotificacionController::class,'destroyAll'])->name('notificaciones.destroyAll');
   Route::apiResource('/finca',FincaController::class)->only(['index','store','update']);
   Route::get('/crear_sesion_finca/{finca}',[FincaController::class,'crear_sesion_finca'])->name('crear_sesion_finca');
   Route::get('/verificar_sesion_finca',[FincaController::class,'verificar_sesion_finca'])->name('verificar_sesion_finca');

    Route::get('/crias_pendiente_capar',[CaparCriaController::class,'index'])->name('capar.index');
    Route::get('/capar_cria/{ganado}',[CaparCriaController::class,'capar'])->name('capar.capar');

    Route::get('/crias_pendiente_numeracion',[AsignarNumeroCriaController::class,'index'])->name('numeracion.index');
    Route::post('/asignar_numero_cria/{ganado}',[AsignarNumeroCriaController::class,'store'])->name('numeracion.store');

    Route::get('/revisiones', TodosRevisiones::class)->name('todasRevisiones');
    Route::get('/servicios', TodosServicios::class)->name('todasServicios');
    Route::get('/pesaje_leche', TodosPesajeLeche::class)->name('todosPesajesLeche');
    Route::get('/partos', TodosPartos::class)->name('todosPartos');

    //rutas peticiones de datos dashboard
     Route::get('dashboard_principal/total_ganado_tipo',[DashboardPrincipalController::class,'totalGanadoTipo'])->name('dashboardPrincipal.totalGanadoTipo');
     Route::get('dashboard_principal/total_personal',[DashboardPrincipalController::class,'totalPersonal'])->name('dashboardPrincipal.totalPersonal');
     Route::get('dashboard_principal/vacas_gestacion',[DashboardPrincipalController::class,'VacasEnGestacion'])->name('dashboardPrincipal.vacasEnGestacion');
     Route::get('dashboard_principal/vacas_productoras',[DashboardPrincipalController::class,'topVacasProductoras'])->name('dashboardPrincipal.topVacasProductoras');
     Route::get('dashboard_principal/vacas_menos_productoras',[DashboardPrincipalController::class,'topVacasMenosProductoras'])->name('dashboardPrincipal.topVacasMenosProductoras');
     Route::get('dashboard_principal/ganado_pendiente_revision',[DashboardPrincipalController::class,'totalGanadoPendienteRevision'])->name('dashboardPrincipal.totalGanadoPendienteRevision');
     Route::get('dashboard_principal/cantidad_novillas_montar',[DashboardPrincipalController::class,'cantidadVacasParaServir'])->name('dashboardPrincipal.cantidadVacasParaServir');
   /*   Route::get('dashboard_principal/menor_insumo',[DashboardPrincipalController::class,'insumoMenorExistencia'])->name('dashboardPrincipal.insumoMenorExistencia');
     Route::get('dashboard_principal/mayor_insumo',[DashboardPrincipalController::class,'insumoMayorExistencia'])->name('dashboardPrincipal.insumoMayorExistencia'); */
     Route::get('dashboard_principal/balance_anual_leche',[DashboardPrincipalController::class,'balanceAnualProduccionLeche'])->name('dashboardPrincipal.balanceAnualProduccionLeche');

    //rutas peticiones de datos dashboard venta leche
   /*  Route::get('/dashboard_venta_leche/precio_actual',[DashboardVentaLecheController::class, 'precioActual'])->name('dashboardVentaLeche.precioActual');
    Route::get('/dashboard_venta_leche/variacion_precio',[DashboardVentaLecheController::class, 'variacionPrecio'])->name('dashboardVentaLeche.variacionPrecio');
    Route::get('/dashboard_venta_leche/ganancias_mes',[DashboardVentaLecheController::class, 'gananciasDelMes'])->name('dashboardVentaLeche.gananciasDelMes');
    Route::get('/dashboard_venta_leche/ventas_mes',[DashboardVentaLecheController::class, 'ventasDelMes'])->name('dashboardVentaLeche.ventasDelMes');
    Route::get('/dashboard_venta_leche/balance_mensual',[DashboardVentaLecheController::class, 'balanceMensual'])->name('dashboardVentaLeche.balanceMensual'); */

    //rutas peticiones de datos dashboard venta ganado
    Route::get('/dashboard_venta_ganado/mejor_comprador',[DashboardVentaGanadoController::class, 'mejorComprador'])->name('dashboardVentaGanado.mejorComprador');
    /* Route::get('/dashboard_venta_ganado/mejor_venta',[DashboardVentaGanadoController::class, 'mejorVenta'])->name('dashboardVentaGanado.mejorVenta');
    Route::get('/dashboard_venta_ganado/peor_venta',[DashboardVentaGanadoController::class, 'peorVenta'])->name('dashboardVentaGanado.peorVenta'); */
    Route::get('/dashboard_venta_ganado/ventas_mes',[DashboardVentaGanadoController::class, 'ventasDelMes'])->name('dashboardVentaGanado.ventasDelMes');
    Route::get('/dashboard_venta_ganado/balance_anual',[DashboardVentaGanadoController::class, 'balanceAnualVentas'])->name('dashboardVentaGanado.balanceAnualVentas');

    //rutas peticiones de datos dashboard fallecimientos
    Route::get('/dashboard_fallecimientos/causas_frecuentes', [DashboardFallecimientosController::class, 'causasMuertesFrecuentes'])->name('dashboardFallecimientos.causasMuertesFrecuentes');


    //rutas de peticiones de datos dashboard jornadas vacunacion
    Route::get('/dashboard_jornadas_vacunacion/proximas_jornadas_vacunacion', [DashboardJornadasVacunacion::class, 'proximasJornadasVacunacion'])->name('dashboardJornadasVacunacion.proximasJornadasVacunacion');

     Route::get('/check_session_activa',checkSesionActivaUsuario::class)->name('checkSession');


    //rutas peticiones datos para rellanr formularios
     Route::get('/novillas_montar',[DatosParaFormulariosController::class,'novillasParaMontar'])->name('datosParaFormularios.novillasParaMontar');
     Route::get('/cargos_personal',[DatosParaFormulariosController::class, 'cargosPersonalDisponible'])->name('datosParaFormularios.cargosPersonal');
     Route::get('/veterinarios',[DatosParaFormulariosController::class, 'veterinariosDisponibles'])->name('datosParaFormularios.veterinariosDisponibles');
     Route::get('/años_ventas_ganado',[DatosParaFormulariosController::class, 'añosVentasGanado'])->name('datosParaFormularios.añosVentasGanado');
     Route::get('/años_produccion_leche',[DatosParaFormulariosController::class, 'añosProduccionLeche'])->name('datosParaFormularios.añosProduccionLeche');
     Route::get('/vacunas_disponibles',[DatosParaFormulariosController::class, 'vacunasDisponibles'])->name('datosParaFormularios.vacunasDisponibles');
     Route::get('/numero_disponible',[DatosParaFormulariosController::class, 'sugerirNumeroDisponibleEnBD'])->name('datosParaFormularios.sugerirNumeroDisponibleEnBD');
     Route::get('/veterinarios_sin_usuario',[DatosParaFormulariosController::class, 'veterinariosSinUsuario'])->name('datosParaFormularios.veterinariosSinUsuario');

    //Respaldo - Restauracion de base de datos
    Route::get('/respaldo', [BackupRestoreBDController::class, 'respaldarBd']);
    Route::get('/restaurar', [BackupRestoreBDController::class, 'restaurarBd']);
    Route::get('/ultimo_respaldo', [BackupRestoreBDController::class, 'obtenerUltimoRespaldo']);

    });
