<?php

namespace App\Http\Controllers;

use App\Http\Resources\LogsEventosResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class LogsEventos extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Gate::allowIf(fn (User $user) => $user->hasRole('admin'));

        $logsEventos = Activity::where('causer_id', $request->user()->id)
        ->where('log_name','!=' ,'login')
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json(['logs_eventos' => LogsEventosResource::collection($logsEventos)]);
    }
}
