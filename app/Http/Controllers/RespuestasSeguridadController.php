<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRespuestasSeguridadRequest;
use App\Http\Requests\UpdateRespuestasSeguridadRequest;
use App\Http\Resources\PreguntasSeguridadCollection;
use App\Http\Resources\RespuestasSeguridadCollection;
use App\Http\Resources\RespuestasSeguridadResource;
use App\Models\RespuestasSeguridad;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
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
        /* los detalles des las respuestas se hacen aqui ya que el metodo del
         modelo user solo se devuelven las preguntas de seguridad que tiene el usuario */
        $query=RespuestasSeguridad::selectRaw('respuestas_seguridad.id,
        preguntas_seguridad.pregunta,
        respuestas_seguridad.updated_at,
        preguntas_seguridad.id as pregunta_seguridad_id')
        ->join('preguntas_seguridad','preguntas_seguridad_id','preguntas_seguridad.id')
        ->where('user_id',Auth::id())->get()    ;


       /* en lugar de enviar las respuestas de seguridad con su pregunta, solo se envian las preguntas
        ya que las respuestas estan cifradas */
     return new RespuestasSeguridadCollection($query);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRespuestasSeguridadRequest $request)
    {
        $user=$request->user();

        $respuestaSeguridad=New RespuestasSeguridad();
        $respuestaSeguridad->preguntas_seguridad_id=$request->pregunta_seguridad_id;
         /* convertir a minúsculas las respuestas para que a la hora de colocar la respuesta no tenga disticion de la primera letra */
            /* esto se hace con el fin de que el usuario pueda ingresar las respuestas en minúsculas y que la comparación sea case insensitive */
        $respuestaUser=strtolower($request->respuesta);
        $respuestaSeguridad->respuesta=Hash::make($respuestaUser);
        $respuestaSeguridad->user_id=$user->id;
        $respuestaSeguridad->save();

        /* no se hace un solo if para evitar consulta innecesaria */
        if(!$user->tiene_preguntas_seguridad)
      {
        //refresh de la tabla para que se actualice el valor de tiene_preguntaSeguridad
        $user->refresh();
        //si el usuario cumple con el minimo de preguntas se guardara en la bd
        if($user->respuestasSeguridad->count() >= 3 ){
            User::where('id',$user->id)->update(['tiene_preguntas_seguridad'=>1]);
        };}


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
        /* convertir a minúsculas las respuestas para que a la hora de colocar la respuesta no tenga disticion de la primera letra,
        esto se hace con el fin de que el usuario pueda ingresar las respuestas en minúsculas y que la comparación sea case insensitive */
       $respuestaUser=strtolower($request->respuesta);

       $respuestaSeguridad->preguntas_seguridad_id=$request->pregunta_seguridad_id;
       $respuestaSeguridad->respuesta=Hash::make($respuestaUser);
       $respuestaSeguridad->save();

        return response()->json(['respuesta_seguridad' => new RespuestasSeguridadResource($respuestaSeguridad)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RespuestasSeguridad $respuestaSeguridad)
    {
        $user=$respuestaSeguridad->user;
        $eliminar=boolval(RespuestasSeguridad::destroy($respuestaSeguridad->id));

        /* para evitar consulta innecesaria, si el usuario no tiene preguntas de seguridad no hace falta chequear si tiene preguntas de seguridad minima */
       if($user->tiene_preguntas_seguridad)
       {
        //refresh de la tabla para que se actualice el valor de tiene_preguntaSeguridad
        $user->refresh();

        /* si la eliminacion hace que el usuario no tenga el minimo de preguntas de seguridad, se cambiara en la bd para poder
        advertir al usuario que no tiene las preguntas de seguridad minimas */
        if($eliminar && $user->respuestasSeguridad->count() < 3)    User::where('id',$user->id)->update(['tiene_preguntas_seguridad'=>0]);}

        return  response()->json(['respuestaSeguridadID' => $eliminar ?  $respuestaSeguridad->id : ''], 200);

    }
}
