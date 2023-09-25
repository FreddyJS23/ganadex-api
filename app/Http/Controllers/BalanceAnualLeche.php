<?php

namespace App\Http\Controllers;

use App\Http\Resources\BalanceAnualLecheCollection;
use App\Models\Leche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceAnualLeche extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $balanceAnualLeche = Leche::selectRaw("DATE_FORMAT(fecha,'%m') as mes")
                                ->selectRaw("AVG(peso_leche) as promedio_pesaje")
                                ->groupBy('fecha')->get();

      return new BalanceAnualLecheCollection($balanceAnualLeche);
    }
}
