<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRespuestasSeguridadRequest;
use App\Http\Requests\UpdateRespuestasSeguridadRequest;
use App\Http\Resources\PreguntasSeguridadCollection;
use App\Models\RespuestasSeguridad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RespuestasSeguridadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /* en lugar de enviar las respuestas de seguridad con su pregunta, solo se envian las preguntas
        ya que las respuestas estan cifradas */
     return new PreguntasSeguridadCollection(Auth::user()->preguntasSeguridad);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRespuestasSeguridadRequest $request)
    {
        $respuestasSeguridad=[];

        //iteraccion para guardar las preguntas de seguridad y las respuestas
        foreach ($request->input('preguntas') as $index => $pregunta) {

           /* convertir a minúsculas las respuestas para que a la hora de colocar la respuesta no tenga disticion de la primera letra */
            /* esto se hace con el fin de que el usuario pueda ingresar las respuestas en minúsculas y que la comparación sea case insensitive */
             $respuesta=strtolower($request->input('respuestas')[$index]);

            $respuestaSeguridad=['preguntas_seguridad_id'=>$pregunta,
            'respuesta'=>Hash::make($respuesta)];

            array_push($respuestasSeguridad,$respuestaSeguridad);
        }

        //si el usuario no tiene preguntas de seguridad, se guarda en la base de datos que ya tiene
        if(!$request->user()->tiene_preguntas_seguridad){
            User::where('id',$request->user()->id)->update(['tiene_preguntas_seguridad'=>1]);
        };

        $request->user()->respuestasSeguridad()->createMany($respuestasSeguridad);

        return response()->json(['message' => 'preguntas de seguridad creadas'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RespuestasSeguridad $respuestasSeguridad)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRespuestasSeguridadRequest $request, RespuestasSeguridad $respuestaSeguridad)
    {
        $respuestaSeguridad->update($request->all());

        return response()->json(['message' => 'pregunta de seguridad actualizadas'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RespuestasSeguridad $respuestaSeguridad)
    {
        return  response()->json(['respuestaSeguridadID' => RespuestasSeguridad::destroy($respuestaSeguridad->id) ?  $respuestaSeguridad->id : ''], 200);

    }
}
