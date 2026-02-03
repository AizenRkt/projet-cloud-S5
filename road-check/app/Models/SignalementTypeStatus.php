<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalementTypeStatus extends Model
{
    protected $table = 'signalement_type_status';
    protected $primaryKey = 'id_signalement_type_status';
    public $timestamps = false;

    protected $fillable = ['code', 'libelle', 'pourcentage'];
}
