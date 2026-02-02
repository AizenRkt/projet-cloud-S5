<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModificationSignalement extends Model
{
    protected $table = 'modification_signalement';
    protected $primaryKey = 'id_modification';
    public $timestamps = false; // We use date_modification

    protected $fillable = [
        'id_signalement',
        'id_utilisateur',
        'statut',
        'budget',
        'surface_m2',
        'id_entreprise',
        'note',
        'date_modification'
    ];
}
