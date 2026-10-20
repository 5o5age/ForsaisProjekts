<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Task extends Model
{
    public const TASK_TYPES = [
        'feeding' => 'Barošana',
        'cleaning' => 'Tīrīšana',
        'care' => 'Aprūpe',
        'medicine' => 'Medikamenti',
        'other' => 'Cits',
    ];

    protected $fillable = ['title', 'task_type', 'horse_id', 'assigned_to', 'feeding_id', 'start_date', 'task_time', 'priority', 'recurrence', 'weekdays', 'month_ranges', 'end_date', 'inventory_item_id', 'consumption', 'notes', 'created_by', 'active'];
    protected function casts(): array { return ['start_date' => 'date', 'end_date' => 'date', 'weekdays' => 'array', 'consumption' => 'decimal:3', 'active' => 'boolean']; }
    public function horse(): BelongsTo { return $this->belongsTo(Horse::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function feeding(): BelongsTo { return $this->belongsTo(Feeding::class); }
    public function inventoryItem(): BelongsTo { return $this->belongsTo(InventoryItem::class); }
    public function completions(): HasMany { return $this->hasMany(TaskCompletion::class); }
    public function occurrences(): HasMany { return $this->hasMany(TaskOccurrence::class); }

    public function originalDateForOccurrence(CarbonImmutable $scheduledDate): ?CarbonImmutable
    {
        $original = $this->originalDatesForScheduledDate($scheduledDate)->first();
        return $original ? CarbonImmutable::parse($original) : null;
    }

    public function originalDatesForScheduledDate(CarbonImmutable $scheduledDate): Collection
    {
        $occurrences = $this->relationLoaded('occurrences') ? $this->occurrences : $this->occurrences()->get();
        $date = $scheduledDate->toDateString();
        $singleOverrides = $occurrences->where('scope', 'single');
        $originalDates = $singleOverrides->filter(fn ($row) => $row->scheduled_date->toDateString() === $date)
            ->map(fn ($row) => $row->original_date->toDateString())->values()->toBase();
        $rules = $occurrences->where('scope', 'future')->sortByDesc(fn ($row) => $row->original_date->toDateString())->values();

        foreach ($rules as $rule) {
            $ruleStart = CarbonImmutable::parse($rule->original_date);
            $ruleTarget = CarbonImmutable::parse($rule->scheduled_date);
            $shiftDays = $ruleStart->diffInDays($ruleTarget, false);
            $candidate = $scheduledDate->subDays($shiftDays);
            if ($candidate->lt($ruleStart)) continue;
            $effectiveRule = $rules->first(fn ($row) => CarbonImmutable::parse($row->original_date)->lte($candidate));
            if (!$effectiveRule || $effectiveRule->id !== $rule->id || !$this->occursOn($candidate)) continue;
            if ($singleOverrides->contains(fn ($row) => $row->original_date->toDateString() === $candidate->toDateString())) continue;
            $originalDates->push($candidate->toDateString());
        }

        $baseOverride = $singleOverrides->first(fn ($row) => $row->original_date->toDateString() === $date);
        $effectiveBaseRule = $rules->first(fn ($row) => CarbonImmutable::parse($row->original_date)->lte($scheduledDate));
        if ($this->occursOn($scheduledDate) && !$baseOverride && !$effectiveBaseRule) $originalDates->push($date);

        return $originalDates->unique()->values();
    }

    public function occursOn(CarbonImmutable $date): bool
    {
        if ($this->active === false) return false;
        $start = CarbonImmutable::parse($this->start_date)->startOfDay();
        $end = $this->end_date ? CarbonImmutable::parse($this->end_date)->startOfDay() : null;
        if ($date->startOfDay()->lt($start) || ($end && $date->startOfDay()->gt($end))) return false;
        return match ($this->recurrence) {
            'once' => $date->isSameDay($start),
            'daily' => true,
            'weekdays' => $date->isWeekday(),
            'weekends' => $date->isWeekend(),
            'custom_weekdays' => in_array($date->isoWeekday(), array_map('intval', $this->weekdays ?? []), true),
            'month_ranges' => $this->dayInMonthRanges($date->day),
            default => false,
        };
    }

    private function dayInMonthRanges(int $day): bool
    {
        if (!$this->month_ranges) return false;
        foreach (preg_split('/[,;]/', $this->month_ranges) ?: [] as $part) {
            if (preg_match('/^\s*(\d{1,2})\s*(?:[-–—]\s*(\d{1,2}))?\s*$/', $part, $m)) {
                $from = (int) $m[1]; $to = isset($m[2]) ? (int) $m[2] : $from;
                if ($day >= $from && $day <= $to) return true;
            }
        }
        return false;
    }

    public function isCompleteOn(string $date): bool
    {
        return $this->completions()->whereDate('occurrence_date', $date)->exists();
    }
}
