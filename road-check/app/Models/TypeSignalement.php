<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeSignalement extends Model
{
    protected $table = 'type_signalement';
    protected $primaryKey = 'id_type_signalement';
    public $timestamps = false;

    protected $fillable = ['nom', 'icon'];

    public function signalements()
    {
        return $this->hasMany(Signalement::class, 'id_type_signalement');
    }
}
