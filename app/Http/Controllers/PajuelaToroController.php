<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePajuelaToroRequest;
use App\Http\Requests\UpdatePajuelaToroRequest;
use App\Http\Resources\PajuelaToroCollection;
use App\Http\Resources\PajuelaToroResource;
use App\Models\PajuelaToro;
use Illuminate\Support\Facades\Auth;

class PajuelaToroController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PajuelaToro::class, 'pajuela_toro');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new PajuelaToroCollection(PajuelaToro::where('hacienda_id', session('hacienda_id'))->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePajuelaToroRequest $request)
    {
        $pajuelaToro = new PajuelaToro();
        $pajuelaToro->fill($request->all());
        $pajuelaToro->hacienda_id = session('hacienda_id');
        $pajuelaToro->save();

        return response()->json(['pajuela_toro' => new PajuelaToroResource($pajuelaToro)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PajuelaToro $pajuelaToro)
    {
        return response()->json(['pajuela_toro' => new PajuelaToroResource($pajuelaToro)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePajuelaToroRequest $request, PajuelaToro $pajuelaToro)
    {
        $pajuelaToro->fill($request->all());
        $pajuelaToro->save();

        return response()->json(['pajuela_toro' => new PajuelaToroResource($pajuelaToro)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PajuelaToro $pajuelaToro)
    {
        return  response()->json(['pajuela_toroID' => PajuelaToro::destroy($pajuelaToro->id) ?  $pajuelaToro->id : ''], 200);
    }
}
