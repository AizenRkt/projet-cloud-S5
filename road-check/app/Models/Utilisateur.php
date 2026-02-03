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
        'password',
        'firebase_uid',
        'nom',
        'prenom',
        'id_role',
        'bloque'
    ];

    protected $hidden = ['password'];

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

    // Bloquer l'utilisateur
    public function block(): void
    {
        $this->bloque = true;
        $this->save();
    }

    // Débloquer l'utilisateur et supprimer les tentatives d'échec
    public function unblock(): void
    {
        $this->bloque = false;
        $this->save();

        // Supprimer les tentatives échouées
        \App\Models\TentativeConnexion::where('id_utilisateur', $this->id_utilisateur)->delete();
    }
}

