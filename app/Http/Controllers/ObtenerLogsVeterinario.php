<?php

namespace App\Http\Controllers;

use App\Http\Resources\LogsVeterinarioResource;
use App\Models\UsuarioVeterinario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class ObtenerLogsVeterinario extends Controller
{

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, UsuarioVeterinario $usuarioVeterinario)
    {

        if (!Gate::allows('view-logs', $usuarioVeterinario)) {
            abort(403);
        }

        $logsUsuarioVeterinario = Activity::where('causer_id', $usuarioVeterinario->user_id)->get();

        return response()->json(['logs' => LogsVeterinarioResource::collection($logsUsuarioVeterinario)]);
    }
}
