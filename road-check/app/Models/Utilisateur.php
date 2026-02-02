<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Utilisateur extends Authenticatable
{
    use HasFactory;

    protected $table = 'utilisateur';
    protected $primaryKey = 'id_utilisateur';
    protected $fillable = [
        'email',
        'password',
        'firebase_uid',
        'nom',
        'prenom',
        'id_role',
        'bloque'
    ];
    public $timestamps = false;

    public function unblock(): void
    {
        $this->bloque = false;
        $this->save();

        DB::table('tentative_connexion')
            ->where('id_utilisateur', $this->id_utilisateur)
            ->delete();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }
}
