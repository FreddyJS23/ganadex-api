<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificacionesCollection;
use App\Models\Notificacion;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notificaciones = Notificacion::where('hacienda_id', session('hacienda_id'))
            ->select('notificacions.id', 'tipo', 'ganado_id', 'leido', 'dias_para_evento')
            ->join('tipos_notificacions', 'tipo_id', 'tipos_notificacions.id')
            ->with('ganado:id,numero')
            ->get();

        return new NotificacionesCollection($notificaciones->groupBy('tipo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(Notificacion $notificacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Notificacion $notificacion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notificacion $notificacion)
    {
        return  response()->json(['notificacionID' => Notificacion::destroy($notificacion->id) ?  $notificacion->id : ''], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyAll()
    {
        $notificaciones = Notificacion::where('hacienda_id', session('hacienda_id'))->get();

        $notificaciones->modelKeys();

        Notificacion::destroy($notificaciones);

        return  response()->json('', 200);
    }
}
