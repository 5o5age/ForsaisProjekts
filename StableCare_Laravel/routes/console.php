<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('stablecare:health', function () {
    $this->info('StableCare komandrindas vide darbojas.');
})->purpose('Pārbauda StableCare komandrindas iestatījumus');
