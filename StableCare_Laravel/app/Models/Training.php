<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = ['horse_id', 'trainer_id', 'training_date', 'training_time', 'training_type', 'duration_minutes', 'status', 'notes', 'results'];
    protected function casts(): array { return ['training_date' => 'date']; }
    public function horse() { return $this->belongsTo(Horse::class); }
    public function trainer() { return $this->belongsTo(User::class, 'trainer_id'); }
}
