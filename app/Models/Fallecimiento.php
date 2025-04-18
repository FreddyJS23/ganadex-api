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

class Fallecimiento extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'causas_fallecimiento_id',
        'fecha',
        'descripcion'
    ];

    protected $casts=[
        'fecha' => Fecha::class,
    ];

    public function causa_fallecimiento(): BelongsTo
    {
        return $this->belongsTo(CausasFallecimiento::class,'causas_fallecimiento_id','id','causas_fallecimiento');
    }

    /**
     * Get the ganado that owns the Fallecimiento
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
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
