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

    // Méthode pour débloquer l'utilisateur
    public function unblock(): void
    {
        $this->bloque = false;
        $this->save();

        // Supprimer les tentatives échouées
        \DB::table('tentative_connexion')
            ->where('id_utilisateur', $this->id_utilisateur)
            ->delete();
    }

    // Méthode statique pour débloquer par email
    public static function unblockByEmail(string $email): bool
    {
        $utilisateur = self::where('email', $email)->first();
        if ($utilisateur && $utilisateur->bloque) {
            $utilisateur->unblock();
            return true;
        }
        return false;
    }
}
