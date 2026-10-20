<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'supervisor', 'trainer', 'veterinarian'];

    public const ROLE_LABELS = [
        'admin' => 'Administrators',
        'supervisor' => 'Uzraugs',
        'trainer' => 'Treneris',
        'veterinarian' => 'Veterinārārsts',
    ];

    protected $fillable = ['name', 'first_name', 'last_name', 'email', 'password', 'role', 'phone', 'is_active', 'stable_id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isSupervisor(): bool { return $this->role === 'supervisor'; }
    public function isTrainer(): bool { return $this->role === 'trainer'; }
    public function isVeterinarian(): bool { return $this->role === 'veterinarian'; }
    public function canManageTasks(): bool { return $this->isAdmin() || $this->isSupervisor(); }
    public function canManageTraining(): bool { return $this->isAdmin() || $this->isTrainer(); }
    public function canManageHealth(): bool { return $this->isAdmin() || $this->isVeterinarian(); }
    public function canManageHorseProfile(): bool { return in_array($this->role, self::ROLES, true); }
    public function canManageInventory(): bool { return $this->isAdmin() || $this->isSupervisor(); }

    public function stable() { return $this->belongsTo(Stable::class); }

    public function createdHorses() { return $this->hasMany(Horse::class, 'created_by'); }
    public function assignedTasks() { return $this->hasMany(Task::class, 'assigned_to'); }
}
