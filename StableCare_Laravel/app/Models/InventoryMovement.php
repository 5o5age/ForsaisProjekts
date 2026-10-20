<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = ['inventory_item_id', 'user_id', 'quantity_delta', 'reason', 'reference_key'];
    protected function casts(): array { return ['quantity_delta' => 'decimal:3']; }
    public function item() { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
