<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Feeding extends Model
{
    protected $fillable = ['horse_id', 'name', 'inventory_item_id', 'amount', 'feed_time', 'recurrence', 'weekdays', 'month_ranges', 'start_date', 'end_date', 'active', 'notes', 'created_by'];
    protected function casts(): array { return ['amount' => 'decimal:3', 'weekdays' => 'array', 'start_date' => 'date', 'end_date' => 'date', 'active' => 'boolean']; }
    public function horse(): BelongsTo { return $this->belongsTo(Horse::class); }
    public function inventoryItem(): BelongsTo { return $this->belongsTo(InventoryItem::class); }
    public function task(): HasOne { return $this->hasOne(Task::class); }
}
