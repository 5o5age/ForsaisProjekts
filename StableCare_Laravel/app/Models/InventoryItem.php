<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = ['stable_id', 'name', 'category', 'quantity', 'unit', 'minimum_quantity', 'location', 'expires_at', 'notes', 'created_by'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'minimum_quantity' => 'decimal:3', 'expires_at' => 'date']; }
    public function stable() { return $this->belongsTo(Stable::class); }
    public function movements(): HasMany { return $this->hasMany(InventoryMovement::class); }
    public function feedings(): HasMany { return $this->hasMany(Feeding::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function getIsLowAttribute(): bool { return (float) $this->quantity <= (float) $this->minimum_quantity; }
}
