<?php

namespace App\Models;

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
        'diagnostico',
        'tratamiento',
        'fecha',
        'personal_id',
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
