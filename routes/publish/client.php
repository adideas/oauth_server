<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:client', 'bindings'])->namespace('Client')->group(
    function () {

        // Routes client

    }
);
