<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCompletion extends Model
{
    public $timestamps = false;
    protected $fillable = ['task_id', 'occurrence_date', 'completed_by', 'notes', 'completed_at'];
    protected function casts(): array { return ['occurrence_date' => 'date', 'completed_at' => 'datetime']; }
    public function task() { return $this->belongsTo(Task::class); }
    public function completer() { return $this->belongsTo(User::class, 'completed_by'); }
}
