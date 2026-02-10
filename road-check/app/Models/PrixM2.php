<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrixM2 extends Model
{
    protected $table = 'prix_m2';
    protected $primaryKey = 'id_prix_m2';
    public $timestamps = false;

    protected $fillable = [
        'date',
        'valeur'
    ];

    protected $casts = [
        'date' => 'datetime',
        'valeur' => 'double'
    ];
}
