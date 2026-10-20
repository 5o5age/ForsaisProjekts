<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedingController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\HorseDocumentController;
use App\Http\Controllers\HorseNoteController;
use App\Http\Controllers\HorseQrController;
use App\Http\Controllers\StableController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/kalendars', CalendarController::class)->name('calendar.index');
    Route::get('/zirgi', [HorseController::class, 'index'])->name('horses.index');
    Route::get('/zirgi/jauns', [HorseController::class, 'create'])->middleware('role:admin,supervisor,trainer,veterinarian')->name('horses.create');
    Route::get('/zirgi/{horse}', [HorseController::class, 'show'])->name('horses.show');
    Route::post('/zirgi', [HorseController::class, 'store'])->middleware('role:admin,supervisor,trainer,veterinarian')->name('horses.store');
    Route::get('/zirgi/{horse}/labot', [HorseController::class, 'edit'])->middleware('role:admin,supervisor,trainer,veterinarian')->name('horses.edit');
    Route::put('/zirgi/{horse}', [HorseController::class, 'update'])->middleware('role:admin,supervisor,trainer,veterinarian')->name('horses.update');
    Route::delete('/zirgi/{horse}', [HorseController::class, 'destroy'])->middleware('role:admin,supervisor,trainer,veterinarian')->name('horses.destroy');
    Route::get('/zirgi/{horse}/qr', HorseQrController::class)->name('horses.qr');
    Route::get('/zirgi/{horse}/dokumenti', [HorseDocumentController::class, 'index'])->name('horses.documents.index');
    Route::get('/zirgi/{horse}/dokumenti/{document}/lejupieladet', [HorseDocumentController::class, 'download'])->name('horses.documents.download');
    Route::post('/zirgi/{horse}/dokumenti', [HorseDocumentController::class, 'store'])->middleware('role:admin,veterinarian')->name('horses.documents.store');
    Route::delete('/zirgi/{horse}/dokumenti/{document}', [HorseDocumentController::class, 'destroy'])->middleware('role:admin,veterinarian')->name('horses.documents.destroy');
    Route::post('/zirgi/{horse}/piezimes', [HorseNoteController::class, 'store'])->name('horses.notes.store');
    Route::delete('/zirgi/{horse}/piezimes/{note}', [HorseNoteController::class, 'destroy'])->name('horses.notes.destroy');

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/uzdevumi', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/uzdevumi/jauns', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/uzdevumi', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/uzdevumi/{task}/labot', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/uzdevumi/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/uzdevumi/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/uzdevumi/{task}/paveikts', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/uzdevumi/{task}/parvietot', [TaskController::class, 'moveOccurrence'])->name('tasks.occurrences.move');
        Route::get('/barosana', [FeedingController::class, 'index'])->name('feedings.index');
        Route::get('/barosana/jauns', [FeedingController::class, 'create'])->name('feedings.create');
        Route::post('/barosana', [FeedingController::class, 'store'])->name('feedings.store');
        Route::get('/barosana/{feeding}/labot', [FeedingController::class, 'edit'])->name('feedings.edit');
        Route::put('/barosana/{feeding}', [FeedingController::class, 'update'])->name('feedings.update');
        Route::delete('/barosana/{feeding}', [FeedingController::class, 'destroy'])->name('feedings.destroy');
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/noliktava', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/noliktava/jauns', [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('/noliktava', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/noliktava/{item}/vesture', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('/noliktava/{item}/labot', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/noliktava/{item}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/noliktava/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('/atskaites', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('/atskaites/uzdevumi.csv', [ReportsController::class, 'exportTasks'])->name('reports.tasks.csv');
        Route::get('/lietotaji', [UserController::class, 'index'])->name('users.index');
        Route::get('/lietotaji/jauns', [UserController::class, 'create'])->name('users.create');
        Route::post('/lietotaji', [UserController::class, 'store'])->name('users.store');
        Route::get('/lietotaji/{user}/labot', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/lietotaji/{user}/piekļuve', [UserController::class, 'updateAccess'])->name('users.access');
        Route::put('/stallis', [StableController::class, 'update'])->name('stable.update');
        Route::post('/stallis/ieluguma-kods', [StableController::class, 'regenerateInviteCode'])->name('stable.invite.refresh');
        Route::put('/lietotaji/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/lietotaji/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::get('/veseliba', [HealthRecordController::class, 'index'])->name('health.index');
    Route::middleware('role:admin,veterinarian')->group(function () {
        Route::get('/veseliba/jauns', [HealthRecordController::class, 'create'])->name('health.create');
        Route::post('/veseliba', [HealthRecordController::class, 'store'])->name('health.store');
        Route::get('/veseliba/{record}/labot', [HealthRecordController::class, 'edit'])->name('health.edit');
        Route::put('/veseliba/{record}', [HealthRecordController::class, 'update'])->name('health.update');
        Route::delete('/veseliba/{record}', [HealthRecordController::class, 'destroy'])->name('health.destroy');
    });

    Route::get('/trenini', [TrainingController::class, 'index'])->name('trainings.index');
    Route::middleware('role:admin,trainer')->group(function () {
        Route::get('/trenini/jauns', [TrainingController::class, 'create'])->name('trainings.create');
        Route::post('/trenini', [TrainingController::class, 'store'])->name('trainings.store');
        Route::get('/trenini/{training}/labot', [TrainingController::class, 'edit'])->name('trainings.edit');
        Route::put('/trenini/{training}', [TrainingController::class, 'update'])->name('trainings.update');
        Route::delete('/trenini/{training}', [TrainingController::class, 'destroy'])->name('trainings.destroy');
    });

    Route::get('/profils', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profils', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profils/parole', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
