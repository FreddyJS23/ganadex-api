<?php

namespace App\Models;

use App\Casts\Fecha;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Revision extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_revision_id',
        'tratamiento',
        'fecha',
        'personal_id',
        'diagnostico',
    ];

    protected $casts=[
        'fecha' =>Fecha::class,
    ];


    /**
     * Get the ganado that owns the Revision
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    /**
     * Get the veterinario that owns the Revision
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function tipoRevision(): BelongsTo
    {
        return $this->belongsTo(TipoRevision::class);
    }

    /**
     * Get the vacuna that owns the Revision
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class);
    }

    /* activar logs de actividades */
    use LogsActivity;

    /*  //si el usuario no es un admin regitrar logs de actividades
    public function tapActivity(Activity $activity, string $eventName)
    {
        Auth::user() &&  Auth::user()->hasRole('admin') && activity()->disableLogging();

    } */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
