<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreToroRequest;
use App\Http\Requests\UpdateToroRequest;
use App\Http\Resources\ToroCollection;
use App\Http\Resources\ToroResource;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Toro;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\select;

class ToroController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Toro::class,'toro');
    }   
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new ToroCollection(Toro::all()->where('user_id',Auth::id()));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreToroRequest $request)
    {
        $ganado=new Ganado($request->all());   
        $ganado->user_id=Auth::id();
        $ganado->tipo_id=GanadoTipo::where('tipo','adulto')->first()->id;
        $ganado->sexo="M";
        $ganado->save();
        
        $toro=new Toro;
        $toro->user_id=Auth::id();
        $toro->ganado()->associate($ganado)->save();

        return response()->json(['toro'=> new ToroResource($toro)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Toro $toro)
    {
        return response()->json(['toro'=> new ToroResource($toro)],200);
    }

 
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateToroRequest $request, Toro $toro)
    {
    
        $toro->ganado->fill($request->all())->save();
    
        return response()->json(['toro'=> new ToroResource($toro)],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Toro $toro)
    {
        return  response()->json(['toroID' => Ganado::destroy($toro->ganado->id) ?  $toro->id : ''], 200);
    }
}
