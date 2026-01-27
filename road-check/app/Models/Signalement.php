<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signalement extends Model
{
    protected $table = 'signalement';
    protected $primaryKey = 'id_signalement';
    public $timestamps = false; // because you use date_signalement

    protected $fillable = [
        'id_utilisateur',
        'latitude',
        'longitude',
        'statut',
        'surface_m2',
        'budget',
        'id_entreprise'
    ];
}
