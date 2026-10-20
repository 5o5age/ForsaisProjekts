<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Task;
use App\Models\TaskCompletion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskCompletionService
{
    public function setCompleted(Task $task, string $date, bool $completed, ?int $userId, ?string $notes = null): void
    {
        $when = CarbonImmutable::parse($date);
        if (!$task->occursOn($when)) {
            throw ValidationException::withMessages(['date' => 'Šajā datumā uzdevums nav paredzēts.']);
        }

        DB::transaction(function () use ($task, $date, $completed, $userId, $notes) {
            $lockedTask = Task::query()->lockForUpdate()->findOrFail($task->id);
            $record = TaskCompletion::query()->where('task_id', $lockedTask->id)->whereDate('occurrence_date', $date)->lockForUpdate()->first();
            if ($completed) {
                if ($record) {
                    $record->update(['completed_by' => $userId, 'notes' => $notes, 'completed_at' => now()]);
                    return;
                }
                if ($lockedTask->inventory_item_id && (float) $lockedTask->consumption > 0) {
                    $item = InventoryItem::query()->lockForUpdate()->findOrFail($lockedTask->inventory_item_id);
                    $amount = (float) $lockedTask->consumption;
                    if ((float) $item->quantity + 0.000001 < $amount) {
                        throw ValidationException::withMessages(['inventory' => "Nepietiek krājuma “{$item->name}”. Pieejams: {$item->quantity} {$item->unit}."]);
                    }
                    $item->quantity = round((float) $item->quantity - $amount, 3);
                    $item->save();
                    InventoryMovement::create([
                        'inventory_item_id' => $item->id,
                        'user_id' => $userId,
                        'quantity_delta' => -$amount,
                        'reason' => 'Patēriņš: '.$lockedTask->title.' ('.$date.')',
                        'reference_key' => 'task:'.$lockedTask->id.':'.$date,
                    ]);
                }
                TaskCompletion::create(['task_id' => $lockedTask->id, 'occurrence_date' => $date, 'completed_by' => $userId, 'notes' => $notes, 'completed_at' => now()]);
                return;
            }

            if (!$record) return;
            $movement = InventoryMovement::query()->where('reference_key', 'task:'.$lockedTask->id.':'.$date)->lockForUpdate()->first();
            if ($movement && $movement->quantity_delta < 0) {
                $item = InventoryItem::query()->lockForUpdate()->find($movement->inventory_item_id);
                if ($item) {
                    $item->quantity = round((float) $item->quantity + abs((float) $movement->quantity_delta), 3);
                    $item->save();
                }
                $movement->delete();
            }
            $record->delete();
        }, 3);
    }
}
