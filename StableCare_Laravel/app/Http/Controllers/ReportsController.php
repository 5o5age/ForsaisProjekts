<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Task;
use App\Models\Training;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    private function range(Request $request): array
    {
        $data = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        $from = CarbonImmutable::parse($data['from_date'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($data['to_date'] ?? now()->toDateString())->startOfDay();
        if ($to->lt($from)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['to_date' => 'Beigu datumam jābūt pēc sākuma datuma.']);
        }
        abort_if($from->diffInDays($to) > 366, 422, 'Atskaites periods nevar pārsniegt 367 dienas.');
        return [$from, $to];
    }

    /** @return Collection<int, array{date:string,task:Task,done:bool}> */
    private function occurrences(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $stableId = auth()->user()->stable_id;
        $tasks = Task::with(['horse', 'assignee', 'completions'])
            ->when(Schema::hasColumn('tasks', 'active'), fn ($query) => $query->where('active', true))
            ->whereHas('horse', fn ($query) => $query->where('stable_id', $stableId))
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString()))
            ->orderBy('task_time')->get();
        $rows = collect();
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $dateString = $date->toDateString();
            foreach ($tasks as $task) {
                if (!$task->occursOn($date)) continue;
                $done = $task->completions->contains(fn ($completion) => $completion->occurrence_date->toDateString() === $dateString);
                $rows->push(['date' => $dateString, 'task' => $task, 'done' => $done]);
            }
        }
        return $rows;
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        [$from, $to] = $this->range($request);
        $rows = $this->occurrences($from, $to);
        $stableId = $request->user()->stable_id;
        $healthCount = HealthRecord::whereHas('horse', fn ($query) => $query->where('stable_id', $stableId))
            ->whereBetween('record_date', [$from->toDateString(), $to->toDateString()])->count();
        $trainingCount = Training::whereHas('horse', fn ($query) => $query->where('stable_id', $stableId))
            ->whereBetween('training_date', [$from->toDateString(), $to->toDateString()])->count();
        $stockMovementCount = InventoryMovement::whereHas('item', fn ($query) => $query->where('stable_id', $stableId))
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->count();
        $lowStockCount = InventoryItem::where('stable_id', $stableId)->whereColumn('quantity', '<=', 'minimum_quantity')->count();
        return view('reports.index', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'completedCount' => $rows->where('done', true)->count(),
            'plannedCount' => $rows->where('done', false)->count(),
            'healthCount' => $healthCount,
            'trainingCount' => $trainingCount,
            'stockMovementCount' => $stockMovementCount,
            'lowStockCount' => $lowStockCount,
        ]);
    }

    public function exportTasks(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        [$from, $to] = $this->range($request);
        $rows = $this->occurrences($from, $to);
        $filename = 'stablecare-uzdevumi-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';
        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM helps Latvian diacritics display correctly in Excel.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Datums', 'Laiks', 'Zirgs', 'Uzdevums', 'Veids', 'Atbildīgais', 'Statuss', 'Piezīmes'], ';', '"', '\\');
            foreach ($rows as $row) {
                /** @var Task $task */ $task = $row['task'];
                fputcsv($output, [
                    $row['date'],
                    $task->task_time ? substr((string) $task->task_time, 0, 5) : '',
                    $task->horse?->name ?? '',
                    $task->title,
                    $task->task_type,
                    $task->assignee?->name ?? '',
                    $row['done'] ? 'Paveikts' : 'Plānots',
                    $task->notes ?? '',
                ], ';', '"', '\\');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
