<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthRecord extends Model
{
    protected $fillable = ['horse_id', 'veterinarian_id', 'record_type', 'record_date', 'title', 'diagnosis', 'notes', 'next_date'];
    protected function casts(): array { return ['record_date' => 'date', 'next_date' => 'date']; }
    public function horse() { return $this->belongsTo(Horse::class); }
    public function veterinarian() { return $this->belongsTo(User::class, 'veterinarian_id'); }
}
