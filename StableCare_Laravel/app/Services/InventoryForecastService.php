<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class InventoryForecastService
{
    /** @return array{label:string, low:bool, depletion_date:?CarbonImmutable, minimum_date:?CarbonImmutable} */
    public function forecast(InventoryItem $item, Collection $tasks, CarbonImmutable $today, int $days = 365): array
    {
        $remaining = (float) $item->quantity;
        $minimumDate = $remaining <= (float) $item->minimum_quantity ? $today : null;
        $depletionDate = null;

        for ($offset = 0; $offset <= $days; $offset++) {
            $date = $today->addDays($offset);
            foreach ($tasks as $task) {
                if ($task->active === false || (int) $task->inventory_item_id !== (int) $item->id) continue;

                foreach ($task->originalDatesForScheduledDate($date) as $originalDate) {
                    if ($task->completions->contains(fn ($completion) => $completion->occurrence_date->toDateString() === $originalDate)) continue;
                    $amount = (float) $task->consumption;
                    if ($amount <= 0) continue;
                    if ($remaining + 0.000001 < $amount) {
                        $depletionDate = $date;
                        break 3;
                    }
                    $remaining -= $amount;
                    if (!$minimumDate && $remaining <= (float) $item->minimum_quantity) $minimumDate = $date;
                }
            }
        }

        $label = $depletionDate
            ? 'Nepietiks '.($depletionDate->isSameDay($today) ? 'šodien' : 'no '.$depletionDate->format('d.m.Y')).'; krājums zem minimuma '.($minimumDate?->format('d.m.Y') ?? '—')
            : ($minimumDate ? 'Zem minimuma no '.$minimumDate->format('d.m.Y').' · krājums pietiks vismaz '.$days.' dienas' : 'Krājums pietiks vismaz '.$days.' dienas');

        return [
            'label' => $label,
            'low' => (bool) (($depletionDate && $depletionDate->lte($today->addDays(7))) || ($minimumDate && $minimumDate->lte($today->addDays(7)))),
            'depletion_date' => $depletionDate,
            'minimum_date' => $minimumDate,
        ];
    }
}
