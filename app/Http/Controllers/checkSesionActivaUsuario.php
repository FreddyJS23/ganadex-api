<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class checkSesionActivaUsuario extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
       if( Auth::check()) return response()->json([]);
        else return response()->json([], 401);
    }
}
