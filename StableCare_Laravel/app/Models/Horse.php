<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horse extends Model
{
    protected $fillable = ['stable_id', 'name', 'birth_date', 'breed', 'sex', 'colour', 'chip_number', 'owner_name', 'weight_kg', 'status', 'notes', 'created_by'];
    protected function casts(): array { return ['birth_date' => 'date', 'weight_kg' => 'decimal:2']; }
    public function stable() { return $this->belongsTo(Stable::class); }
    public function documents(): HasMany { return $this->hasMany(HorseDocument::class); }
    public function horseNotes(): HasMany { return $this->hasMany(HorseNote::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function feedings(): HasMany { return $this->hasMany(Feeding::class); }
    public function healthRecords(): HasMany { return $this->hasMany(HealthRecord::class); }
    public function trainings(): HasMany { return $this->hasMany(Training::class); }
}
