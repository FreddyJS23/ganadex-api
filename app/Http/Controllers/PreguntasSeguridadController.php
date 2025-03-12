<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePreguntasSeguridadRequest;
use App\Http\Requests\UpdatePreguntasSeguridadRequest;
use App\Http\Resources\PreguntasSeguridadCollection;
use App\Models\PreguntasSeguridad;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;

class PreguntasSeguridadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuario=Auth::user();
        /* estas preguntas estan sin uso por el usuario */
        $preguntasSeguridad=PreguntasSeguridad::select('preguntas_seguridad.id','pregunta','user_id')
        ->leftJoin('respuestas_seguridad',function (JoinClause $join) use ($usuario) {
            $join->on('preguntas_seguridad.id','=','respuestas_seguridad.preguntas_seguridad_id')
            ->where('respuestas_seguridad.user_id',$usuario->id);
        })
          ->whereNull('preguntas_seguridad_id')
         ->get();


        return new PreguntasSeguridadCollection($preguntasSeguridad);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
   /*  public function store(StorePreguntasSeguridadRequest $request)
    {
        //
    } */

    /**
     * Display the specified resource.
     */
    public function show(PreguntasSeguridad $preguntasSeguridad)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PreguntasSeguridad $preguntasSeguridad)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePreguntasSeguridadRequest $request, PreguntasSeguridad $preguntasSeguridad)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PreguntasSeguridad $preguntasSeguridad)
    {
        //
    }
}
