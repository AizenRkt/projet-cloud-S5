<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'utilisateur';
    protected $primaryKey = 'id_utilisateur';

    protected $fillable = [
        'email',
        'firebase_uid',
        'nom',
        'prenom',
        'id_role',
        'bloque',
    ];

    protected $hidden = [
        'firebase_uid',
    ];

    protected function casts(): array
    {
        return [
            'bloque' => 'boolean',
            'date_creation' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function signalements()
    {
        return $this->hasMany(Signalement::class, 'id_utilisateur', 'id_utilisateur');
    }
}
