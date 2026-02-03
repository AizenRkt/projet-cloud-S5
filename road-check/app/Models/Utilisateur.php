<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Utilisateur extends Authenticatable
{
    use HasFactory;

    // Table PostgreSQL
    protected $table = 'utilisateur';

    // Clé primaire
    protected $primaryKey = 'id_utilisateur';

    // Champs autorisés pour insert/update
    protected $fillable = [
        'email',
        'password',
        'firebase_uid',
        'nom',
        'prenom',
        'id_role',
        'bloque'
    ];

    // Désactiver timestamps si tu utilises `date_creation`
    public $timestamps = false;
}
