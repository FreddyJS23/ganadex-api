<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJornada_vacunacionRequest;
use App\Http\Requests\UpdateJornada_vacunacionRequest;
use App\Http\Resources\JornadaVacunacionCollection;
use App\Http\Resources\JornadaVacunacionResource;
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
        return new JornadaVacunacionCollection(Jornada_vacunacion::whereBelongsTo(Auth::user())->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJornada_vacunacionRequest $request)
    {
        $intervaloDosis=Vacuna::find($request->input('vacuna_id'))->intervalo_dosis;
        $proximaDosis=Carbon::create($request->input('fecha_fin'))->addDays($intervaloDosis)->format('Y-m-d');

        $jornadaVacunacion = new Jornada_vacunacion();
        $jornadaVacunacion->fill($request->all());
        $jornadaVacunacion->user_id = Auth::id();
        $jornadaVacunacion->prox_dosis = $proximaDosis;
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
