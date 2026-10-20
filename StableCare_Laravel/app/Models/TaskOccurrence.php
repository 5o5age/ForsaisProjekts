<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskOccurrence extends Model
{
    protected $fillable = ['task_id', 'original_date', 'scheduled_date', 'scope', 'updated_by'];

    protected function casts(): array
    {
        return ['original_date' => 'date', 'scheduled_date' => 'date'];
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
