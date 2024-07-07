<?php

namespace App\Http\Controllers;

use App\Events\RevisionDescarte;
use App\Events\RevisionPrenada;
use App\Http\Requests\StoreRevisionRequest;
use App\Http\Requests\UpdateRevisionRequest;
use App\Http\Resources\RevisionCollection;
use App\Http\Resources\RevisionResource;
use App\Models\Ganado;
use App\Models\Revision;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;

class RevisionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Revision::class,'revision');
    }   
    
    /**
     * Display a listing of the resource.
     */
    public function index(Ganado $ganado)
    {
        return new RevisionCollection(Revision::whereBelongsTo($ganado)->with(['veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRevisionRequest $request, Ganado $ganado)
    {
        $fecha=new DateTime();
        $revision= new Revision;
        $revision->fill($request->all());
        $revision->fecha=$fecha->format('Y-m-d');
        $revision->ganado()->associate($ganado)->save();

        
         RevisionPrenada::dispatchIf($revision->diagnostico == 'prenada',$revision);
         
         RevisionDescarte::dispatchIf($revision->diagnostico == 'descartar',$revision);
       
        return response()->json(['revision'=>new RevisionResource($revision->load(['veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }]))],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado,Revision $revision)
    {
        return response()->json(['revision'=>new RevisionResource($revision->load(['veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }]))]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRevisionRequest $request,Ganado $ganado, Revision $revision)
    {
        $revision->fill($request->all());
        $revision->save();

        return response()->json(['revision'=>new RevisionResource($revision->load(['veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }]))],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado, Revision $revision)
    {
        return  response()->json(['revisionID' => Revision::destroy($revision->id) ?  $revision->id : ''], 200);
    }
}
