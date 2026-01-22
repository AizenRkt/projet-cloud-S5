<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signalement extends Model
{
    use HasFactory;

    protected $table = 'signalement';
    protected $primaryKey = 'id_signalement';

    protected $fillable = [
        'id_utilisateur',
        'latitude',
        'longitude',
        'date_signalement',
        'statut',
        'surface_m2',
        'budget',
        'id_entreprise',
    ];

    protected $casts = [
        'date_signalement' => 'datetime',
        'surface_m2' => 'float',
        'budget' => 'float',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise', 'id_entreprise');
    }
}
