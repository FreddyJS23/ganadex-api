<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJornada_vacunacionRequest;
use App\Http\Requests\UpdateJornada_vacunacionRequest;
use App\Http\Resources\JornadaVacunacionCollection;
use App\Http\Resources\JornadaVacunacionResource;
use App\Models\Ganado;
use App\Models\Jornada_vacunacion;
use App\Models\Vacuna;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class JornadaVacunacionController extends Controller
{

     public function __construct() {
        $this->authorizeResource(Jornada_vacunacion::class,'jornada_vacunacion');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new JornadaVacunacionCollection(Jornada_vacunacion::where('finca_id',session('finca_id'))
        ->orderBy('fecha_inicio','desc')
        ->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJornada_vacunacionRequest $request)
    {
        $vacuna=Vacuna::find($request->input('vacuna_id'));
        $cantidadGanadoVacunado=Ganado::selectRaw('ganados.id,tipo')
        ->join('ganado_tipos','ganados.tipo_id','ganado_tipos.id');

        /* iterarar sobre los tipo de animal correspondiente a la vacuna
        para agregar clausulas de busqueda para buscar los ganados de esos tipos*/
        foreach ($vacuna->tipo_animal->toArray() as $key => $tipoAnimalVacuna) {

            if($tipoAnimalVacuna=='rebano') break;

            //eliminar los últimos dos caracteres para no distinguir terminos femeninos y masculinos
            $tipoAnimalVacuna=substr($tipoAnimalVacuna,0,-2);
            $cantidadGanadoVacunado->orWhere('tipo','like',"$tipoAnimalVacuna%");
        }

        $cantidadGanadoVacunado=$cantidadGanadoVacunado->where('finca_id',[session('finca_id')])->count();

        $intervaloDosis=Vacuna::find($request->input('vacuna_id'))->intervalo_dosis;
        $proximaDosis=Carbon::create($request->input('fecha_fin'))->addDays($intervaloDosis)->format('Y-m-d');

        $jornadaVacunacion = new Jornada_vacunacion();
        $jornadaVacunacion->fill($request->all());
        $jornadaVacunacion->finca_id = session('finca_id');
        $jornadaVacunacion->prox_dosis = $proximaDosis;
        $jornadaVacunacion->vacunados=$cantidadGanadoVacunado;
        $jornadaVacunacion->ganado_vacunado=$vacuna->tipo_animal;
        $jornadaVacunacion->save();

        return response()->json(['jornada_vacunacion' => new JornadaVacunacionResource($jornadaVacunacion)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Jornada_vacunacion $jornada_vacunacion)
    {
        return response()->json(['jornada_vacunacion' => new JornadaVacunacionResource($jornada_vacunacion)], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJornada_vacunacionRequest $request, Jornada_vacunacion $jornada_vacunacion)
    {
        $jornada_vacunacion->fill($request->all());
        $jornada_vacunacion->save();

        return response()->json(['jornada_vacunacion' => new JornadaVacunacionResource($jornada_vacunacion)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Jornada_vacunacion $jornada_vacunacion)
    {
        return response()->json(['jornada_vacunacionID' => Jornada_vacunacion::destroy($jornada_vacunacion->id) ? $jornada_vacunacion->id : ''], 200);
    }
}
