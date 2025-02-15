<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonalRequest;
use App\Http\Requests\UpdatePersonalRequest;
use App\Http\Resources\PersonalCollection;
use App\Http\Resources\PersonalResource;
use App\Models\Personal;
use Illuminate\Support\Facades\Auth;

class PersonalController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Personal::class, 'personal');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new PersonalCollection(Personal::where('finca_id', session('finca_id'))->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonalRequest $request)
    {
        $personal=new Personal;
        $personal->fill($request->all());
        $personal->finca_id=session('finca_id');
        $personal->save();
        return response()->json(['personal'=> new PersonalResource($personal)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Personal $personal)
    {
        return response()->json(['personal'=> new PersonalResource($personal)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonalRequest $request, Personal $personal)
    {
        $personal->fill($request->all())->save();

        return response()->json(['personal'=> new PersonalResource($personal)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Personal $personal)
    {
        return  response()->json(['personalID' => Personal::destroy($personal->id) ?  $personal->id : ''], 200);
    }
}
