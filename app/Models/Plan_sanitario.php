<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Plan_sanitario extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'vacuna_id',
    ];

    protected $casts = [
        'ganado_vacunado' => AsArrayObject::class,
    ];

    /**
     * Get the vacuna that owns the Plan_sanitario
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class);
    }

    /**
     * Get the user that owns the Plan_sanitario
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hacienda(): BelongsTo
    {
        return $this->belongsTo(Hacienda::class);
    }

       /* activar logs de actividades */
       use LogsActivity;

       //si el usuario no es un admin regitrar logs de actividades
    public function tapActivity(Activity $activity, string $eventName)
    {
        Auth::user() &&  Auth::user()->hasRole('admin') && activity()->disableLogging();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
