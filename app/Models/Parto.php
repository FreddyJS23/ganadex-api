<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Parto extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'observacion',
        'fecha',
        'personal_id'
    ];

    /**
     * Get the ganado that owns the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    public function partoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * relationship to parto crias
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partoCrias(): HasMany
    {
        return $this->hasMany(PartoCria::class);
    }

    /**
     * Get last of the cria for the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    /* se entiende que es uno porque la mayoria de los partos solo tendran una cria */
    public function ganado_cria(): HasOne
    {
        return $this->partoCrias()->one()->latestOfMany();
    }

    /**
     * Get all of the crias for the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ganado_crias(): HasMany
    {
        return $this->partoCrias()->selectRaw('ganados.id as id,
        observacion,
        parto_id,
        ganados.nombre as nombre,
        ganados.numero as numero,
        ganados.fecha_nacimiento as fecha_nacimiento,
        ganados.sexo as sexo,
        origen_ganados.origen as origen')
        ->join('ganados','ganado_id','ganados.id')
        ->join('origen_ganados','origen_id','origen_ganados.id');
    }

    /**
     * Get the personal that owns the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

       /* activar logs de actividades */
       use LogsActivity;

    /*
       //si el usuario no es un admin regitrar logs de actividades
       public function tapActivity(Activity $activity, string $eventName)
       {
        Auth::user() &&  Auth::user()->hasRole('admin') && activity()->disableLogging();

       } */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
