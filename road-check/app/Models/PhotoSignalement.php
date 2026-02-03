<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoSignalement extends Model
{
    protected $table = 'photo_signalement';
    protected $primaryKey = 'id_photo';
    public $timestamps = false;

    protected $fillable = ['id_signalement', 'path'];

    public function signalement()
    {
        return $this->belongsTo(Signalement::class, 'id_signalement');
    }
}
