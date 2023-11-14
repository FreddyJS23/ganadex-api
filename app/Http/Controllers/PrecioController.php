<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrecioRequest;
use App\Http\Requests\UpdatePrecioRequest;
use App\Http\Resources\PrecioCollection;
use App\Http\Resources\PrecioResource;
use App\Models\Precio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PrecioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new PrecioCollection(Precio::whereBelongsTo(Auth::user())->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePrecioRequest $request)
    {
        $precio = new Precio;
        $precio->fill($request->only('precio'));
        $precio->user_id = Auth::id();
        $precio->fecha = Carbon::now()->format('Y-m-d');
        $precio->save();
        return response()->json(['precio' => new PrecioResource($precio)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Precio $precio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePrecioRequest $request, Precio $precio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Precio $precio)
    {
        //
    }
}
