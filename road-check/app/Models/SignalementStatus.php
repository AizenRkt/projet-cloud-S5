<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalementStatus extends Model
{
    protected $table = 'signalement_status';
    protected $primaryKey = 'id_signalement_status';
    public $timestamps = false;

    protected $fillable = [
        'id_signalement',
        'id_signalement_type_status',
        'date_modification'
    ];

    public function signalement()
    {
        return $this->belongsTo(Signalement::class, 'id_signalement');
    }

    public function typeStatus()
    {
        return $this->belongsTo(SignalementTypeStatus::class, 'id_signalement_type_status');
    }
}
