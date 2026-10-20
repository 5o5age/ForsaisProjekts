<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorseNote extends Model
{
    protected $fillable = ['horse_id', 'user_id', 'body'];
    public function horse() { return $this->belongsTo(Horse::class); }
    public function author() { return $this->belongsTo(User::class, 'user_id'); }
}
