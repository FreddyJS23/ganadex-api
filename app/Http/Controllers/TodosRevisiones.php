<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodosRevisionesCollection;
use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosRevisiones extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        return new TodosRevisionesCollection(Ganado::doesntHave('toro')->withCount('revision')->where('finca_id', session('finca_id'))->get());
    }
}
