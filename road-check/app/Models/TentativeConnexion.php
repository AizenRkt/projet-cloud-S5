<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TentativeConnexion extends Model
{
    protected $table = 'tentative_connexion';
    protected $primaryKey = 'id_tentative';
    public $timestamps = false;
    protected $fillable = [
        'id_utilisateur',
        'date_tentative',
        'succes',
    ];
}
