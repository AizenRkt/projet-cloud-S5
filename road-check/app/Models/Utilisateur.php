<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Utilisateur extends Authenticatable
{
    use HasFactory;

    protected $table = 'utilisateur';
    protected $primaryKey = 'id_utilisateur';
    public $timestamps = false;

    protected $fillable = [
        'email',
        'firebase_uid',
        'nom',
        'prenom',
        'id_role',
        'bloque'
    ];

    // Relation avec Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    // Relation avec Signalements
    public function signalements()
    {
        return $this->hasMany(Signalement::class, 'id_utilisateur');
    }

    // Relation avec TentativeConnexion
    public function tentatives()
    {
        return $this->hasMany(TentativeConnexion::class, 'id_utilisateur');
    }
}
