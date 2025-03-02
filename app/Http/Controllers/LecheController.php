<?php

namespace App\Http\Controllers;

use App\Events\PesajeLecheHecho;
use App\Http\Requests\StoreLecheRequest;
use App\Http\Requests\UpdateLecheRequest;
use App\Http\Resources\LecheCollection;
use App\Http\Resources\LecheResource;
use App\Models\Ganado;
use App\Models\Leche;
use DateTime;
use Illuminate\Support\Facades\Auth;

class LecheController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Leche::class, 'pesaje_leche');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Ganado $ganado)
    {
        return new LecheCollection(Leche::whereBelongsTo($ganado)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLecheRequest $request, Ganado $ganado)
    {
        $leche = new Leche();
        $leche->fill($request->all());
        $leche->hacienda_id = session('hacienda_id');
        $leche->ganado()->associate($ganado);
        $leche->save();

        PesajeLecheHecho::dispatch($ganado);

        return response()->json(['pesaje_leche' => new LecheResource($leche)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado, Leche $pesaje_leche)
    {
        return response()->json(['pesaje_leche' => new LecheResource($pesaje_leche)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLecheRequest $request, Ganado $ganado, Leche $pesaje_leche)
    {
        $pesaje_leche->fill($request->all());
        $pesaje_leche->save();
        return response()->json(['pesaje_leche' => new LecheResource($pesaje_leche)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado, Leche $pesaje_leche)
    {
        return  response()->json(['pesajeLecheID' => Leche::destroy($pesaje_leche->id) ?  $pesaje_leche->id : ''], 200);
    }
}
