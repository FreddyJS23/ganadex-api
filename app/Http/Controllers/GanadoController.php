<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGanadoRequest;
use App\Http\Requests\UpdateGanadoRequest;
use App\Http\Resources\GanadoCollection;
use App\Http\Resources\GanadoResource;
use App\Http\Resources\LecheResource;
use App\Http\Resources\PartoResource;
use App\Http\Resources\RevisionResource;
use App\Http\Resources\ServicioResource;
use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Leche;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class GanadoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ganado::class,'ganado');
    }   
    
    
    public array $estado=['estado_id'];
    public array $peso=['peso_nacimiento', 'peso_destete','peso_2year','peso_actual'];
    public array $vendido=['precio','comprador_id'];
    /**
     * Display a listing of the resource.
     */
    public function index() :ResourceCollection
    {
        return new GanadoCollection(
            Ganado::doesntHave('toro')
            ->doesntHave('fallecimiento')
            ->doesntHave('venta')
            ->where('user_id',Auth::id())
            ->with(['peso','evento','estados'])
            ->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGanadoRequest $request) :JsonResponse
    {
      $ganado=new Ganado;
      $ganado->sexo = "H";
      $ganado->fill($request->except($this->estado + $this->peso));
      $ganado->user_id=Auth::id();
      $ganado->save();
     
      //estado fallecido
      $request->only($this->estado)['estado_id'][0] == 2 && $ganado->fallecimiento()->create(
        [
            'fecha'=>$request->input('fecha_fallecimiento'),
            'causa'=>$request->input('causa')
    ]);
     
    //estado vendido
      $request->only($this->estado)['estado_id'][0] == 5 && $ganado->venta()->create([
        'fecha'=>$request->input('fecha_venta'),
        'precio'=>$request->input('precio'),
        'comprador_id'=>$request->input('comprador_id')
    ]);
   
      $ganado->peso()->create($request->only($this->peso));
      $ganado->estados()->sync($request->only($this->estado)['estado_id']);
      $ganado->evento()->create();  
     
      return response()->json(['ganado'=>new GanadoResource($ganado)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado)
    {
        $ultimaRevision = $ganado->revisionReciente;
        $ultimoServicio = $ganado->servicioReciente;
        $ultimoPesajeLeche = $ganado->pesajeLecheReciente;
        $ultimoParto = $ganado->partoReciente;
        $ganado->loadCount('servicios')->loadCount('revision')->loadCount('parto');
        $efectividad = fn (int $resultadoAlcanzado, int $resultadoPrevisto) => $resultadoAlcanzado * 100 / $resultadoPrevisto;

        $mejorPesajesLeche = Leche::where('ganado_id', $ganado->id)->orderBy('peso_leche', 'desc')->first();
        $peorPesajesLeche = Leche::where('ganado_id', $ganado->id)->orderBy('peso_leche', 'asc')->first();
        $estadoProduccionLeche = $ganado->estados->contains('estado', 'lactancia') ? "En producción" : 'Inactiva';

        return response()->json([
            'ganado' => new GanadoResource($ganado),
            'servicio_reciente' => $ultimoServicio ? new ServicioResource($ultimoServicio) : null,
            'total_servicios' => $ganado->servicios_count,
            'revision_reciente' => $ultimaRevision ? new RevisionResource($ultimaRevision) : null,
            'total_revisiones' => $ganado->revision_count,
            'parto_reciente' => $ultimoParto ? new PartoResource($ultimoParto) : null,
            'total_partos' => $ganado->parto_count,
            'efectividad' => $ganado->parto_count ? round($efectividad($ganado->parto_count, $ganado->servicios_count), 2) : null,
            'info_pesajes_leche' => (object)([
                'reciente' => $ultimoPesajeLeche ? new LecheResource($ultimoPesajeLeche) : null,
                'mejor' => $ultimoPesajeLeche ? new LecheResource($mejorPesajesLeche) : null,
                'peor' => $ultimoPesajeLeche ? new LecheResource($peorPesajesLeche) : null,
                'estado' => $estadoProduccionLeche
            ])
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGanadoRequest $request, Ganado $ganado)
    {
        $ganado->fill($request->except($this->peso + $this->estado))->save();
        $ganado->peso->fill($request->only($this->peso))->save();
        $ganado->estados()->sync($request->only($this->estado)['estado_id']);
       
        return response()->json(['ganado'=>new GanadoResource($ganado)],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado)
    {
        return  response()->json(['ganadoID' => Ganado::destroy($ganado->id) ?  $ganado->id : ''], 200);
    }
}
