<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entreprise extends Model
{
    use HasFactory;

    protected $table = 'entreprise';
    protected $primaryKey = 'id_entreprise';

    protected $fillable = [
        'nom',
    ];

    public function signalements()
    {
        return $this->hasMany(Signalement::class, 'id_entreprise', 'id_entreprise');
    }
}
