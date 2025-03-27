<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlan_sanitarioRequest;
use App\Http\Requests\UpdatePlan_sanitarioRequest;
use App\Http\Resources\PlanSanitarioCollection;
use App\Http\Resources\PlanSanitarioResource;
use App\Models\Ganado;
use App\Models\Plan_sanitario;
use App\Models\Vacuna;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PlanSanitarioController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Plan_sanitario::class, 'plan_sanitario');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new PlanSanitarioCollection(
            Plan_sanitario::where('hacienda_id', session('hacienda_id'))
                ->orderBy('fecha_inicio', 'desc')
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlan_sanitarioRequest $request)
    {
        $vacuna = Vacuna::find($request->input('vacuna_id'));
        $cantidadGanadoVacunado = Ganado::selectRaw('ganados.id,tipo')
        ->where('hacienda_id', session('hacienda_id'))
        ->join('ganado_tipos', 'ganados.tipo_id', 'ganado_tipos.id');

        /* iterarar sobre los tipo de animal correspondiente a la vacuna
        para agregar clausulas de busqueda para buscar los ganados de esos tipos*/
        foreach ($vacuna->tipo_animal->toArray() as $key => $tipoAnimalVacuna) {
            if ($tipoAnimalVacuna == 'rebano') {
                break;
            }

            //eliminar los últimos dos caracteres para no distinguir terminos femeninos y masculinos
            $tipoAnimalVacuna = substr((string) $tipoAnimalVacuna, 0, -2);
            $cantidadGanadoVacunado->orWhere('tipo', 'like', "$tipoAnimalVacuna%");
        }

        $cantidadGanadoVacunado = $cantidadGanadoVacunado
        ->whereRelation('estados', 'estado','!=', 'fallecido')
        ->whereRelation('estados', 'estado','!=', 'vendido')
        ->where('hacienda_id', session('hacienda_id'))->count();

        $intervaloDosis = Vacuna::find($request->input('vacuna_id'))->intervalo_dosis;
        $proximaDosis = Carbon::create($request->input('fecha_fin'))->addDays($intervaloDosis)->format('Y-m-d');

        $jornadaVacunacion = new Plan_sanitario();
        $jornadaVacunacion->fill($request->all());
        $jornadaVacunacion->hacienda_id = session('hacienda_id');
        $jornadaVacunacion->prox_dosis = $proximaDosis;
        $jornadaVacunacion->vacunados = $cantidadGanadoVacunado;
        $jornadaVacunacion->ganado_vacunado = $vacuna->tipo_animal;
        $jornadaVacunacion->save();

        return response()->json(['plan_sanitario' => new PlanSanitarioResource($jornadaVacunacion)], 201);
    }

    public function planesSanitarioPendientes()
    {
        return new PlanSanitarioCollection(
            Plan_sanitario::where('hacienda_id', session('hacienda_id'))
                ->orderBy('fecha_inicio', 'desc')
                ->where('prox_dosis','<=',now()->format('Y-m-d'))
                ->get()
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Plan_sanitario $plan_sanitario)
    {
        return response()->json(['plan_sanitario' => new PlanSanitarioResource($plan_sanitario)], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlan_sanitarioRequest $request, Plan_sanitario $plan_sanitario)
    {
        $plan_sanitario->fill($request->all());
        $plan_sanitario->save();

        return response()->json(['plan_sanitario' => new PlanSanitarioResource($plan_sanitario)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan_sanitario $plan_sanitario)
    {
        return response()->json(['plan_sanitarioID' => Plan_sanitario::destroy($plan_sanitario->id) ? $plan_sanitario->id : ''], 200);
    }
}
