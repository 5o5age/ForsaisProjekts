<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskOccurrenceService
{
    public function move(Task $task, string $originalDate, string $newDate, string $scope, ?int $userId): void
    {
        $original = CarbonImmutable::parse($originalDate)->startOfDay();
        $target = CarbonImmutable::parse($newDate)->startOfDay();
        if (!$task->occursOn($original)) {
            throw ValidationException::withMessages(['date' => 'Šajā datumā šim uzdevumam nav paredzēta reize.']);
        }
        if (!in_array($scope, ['single', 'future'], true)) {
            throw ValidationException::withMessages(['scope' => 'Izvēlies, kuras reizes pārvietot.']);
        }

        DB::transaction(function () use ($task, $original, $target, $scope, $userId) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->id);
            TaskOccurrence::updateOrCreate(
                ['task_id' => $locked->id, 'original_date' => $original->toDateString()],
                ['scheduled_date' => $target->toDateString(), 'scope' => $scope, 'updated_by' => $userId]
            );
        }, 3);
    }
}
