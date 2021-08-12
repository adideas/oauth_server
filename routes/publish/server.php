<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:server', 'bindings'])->namespace('Server')->group(
    function () {

        // Routes servers

    }
);
