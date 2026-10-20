<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/par-mums', 'info', [
    'title' => 'Par mums',
    'description' => 'Mana PHP mājaslapa ir neliels projekts, kurā vari izveidot kontu un droši pieslēgties.',
])->name('about');

Route::view('/kontakti', 'info', [
    'title' => 'Kontakti',
    'description' => 'Informācija par saziņu tiks pievienota šeit. Ja tev vēl nav konta, vari reģistrēties vai pieslēgties.',
])->name('contact');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
