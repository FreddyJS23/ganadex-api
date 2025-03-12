<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read ?int $id
 * @property string $usuario
 * @property ?string $email
 * @property-read ?DateTimeInterface $email_verified_at
 * @property string $password
 * @property-read ?string $remember_token
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the configuracion associated with the User
     */
    public function configuracion(): HasOne
    {
        return $this->hasOne(Configuracion::class);
    }

    /**
     * Get all of the haciendas for the User
     */
    public function haciendas(): HasMany
    {
        return $this->hasMany(Hacienda::class,);
    }

    /**
     * Get all of the usuariosVeterinario for the User
     */
    public function usuariosVeterinario(): HasMany
    {
        return $this
            ->hasMany(UsuarioVeterinario::class, 'admin_id')
            ->with('veterinario');
    }

    /**
     * relationship to respuestasSeguridad
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function respuestasSeguridad(): HasMany
    {
        return $this->hasMany(RespuestasSeguridad::class);
    }

    /**
     * Get all of the preguntasSeguridad saved for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function preguntasSeguridad(): HasMany
    {
         return $this->respuestasSeguridad()->selectRaw('respuestas_seguridad.id, preguntas_seguridad.pregunta')
        ->join('preguntas_seguridad','preguntas_seguridad_id','preguntas_seguridad.id');
    }



}
