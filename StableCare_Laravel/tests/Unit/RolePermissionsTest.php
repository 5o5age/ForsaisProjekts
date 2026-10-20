<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_roles_have_separated_management_capabilities(): void
    {
        $admin = new User(['role' => 'admin']);
        $supervisor = new User(['role' => 'supervisor']);
        $trainer = new User(['role' => 'trainer']);
        $vet = new User(['role' => 'veterinarian']);
        $this->assertTrue($admin->canManageTasks() && $admin->canManageTraining() && $admin->canManageHealth());
        $this->assertTrue($admin->canManageHorseProfile() && $supervisor->canManageHorseProfile() && $trainer->canManageHorseProfile() && $vet->canManageHorseProfile());
        $this->assertTrue($supervisor->canManageInventory());
        $this->assertFalse($trainer->canManageInventory());
        $this->assertFalse($vet->canManageInventory());
        $this->assertTrue($supervisor->canManageTasks());
        $this->assertFalse($supervisor->canManageTraining());
        $this->assertTrue($trainer->canManageTraining());
        $this->assertFalse($trainer->canManageHealth());
        $this->assertTrue($vet->canManageHealth());
        $this->assertFalse($vet->canManageTasks());
    }
}
