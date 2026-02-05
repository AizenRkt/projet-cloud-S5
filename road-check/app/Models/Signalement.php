<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signalement extends Model
{
    protected $table = 'signalement';
    protected $primaryKey = 'id_signalement';
    public $timestamps = false;

    protected $fillable = [
        'id_type_signalement',
        'id_entreprise',
        'id_utilisateur',
        'latitude',
        'longitude',
        'description',
        'surface_m2',
        'budget',
        'date_signalement',
        'synced_to_firebase',
        'firebase_id',
        'last_sync_attempt',
        'sync_error'
    ];

    protected $casts = [
        'synced_to_firebase' => 'boolean',
        'last_sync_attempt' => 'datetime'
    ];

    // Relations
    public function typeSignalement()
    {
        return $this->belongsTo(TypeSignalement::class, 'id_type_signalement');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function statuts()
    {
        return $this->hasMany(SignalementStatus::class, 'id_signalement');
    }

    public function dernierStatut()
    {
        return $this->hasOne(SignalementStatus::class, 'id_signalement')->latestOfMany('date_modification');
    }

    public function photos()
    {
        return $this->hasMany(PhotoSignalement::class, 'id_signalement');
    }
}
