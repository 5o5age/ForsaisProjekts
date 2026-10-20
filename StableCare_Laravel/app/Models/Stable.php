<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stable extends Model
{
    protected $fillable = ['name', 'address', 'subscription_type', 'invite_code', 'created_by'];

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function horses(): HasMany { return $this->hasMany(Horse::class); }
    public function inventoryItems(): HasMany { return $this->hasMany(InventoryItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
