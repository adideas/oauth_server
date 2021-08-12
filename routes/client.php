<?php

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;

Route::group(
    [
        'prefix'    => 'client',
        'namespace' => Helpers::ROOT_NAMESPACE(['Http', 'Controllers', 'Client']),
        'middleware' => ['auth:client', 'bindings']
    ],
    function ($router) {
        /*
        |--------------------------------------------------------------------------
        | регистрация маршрута проверки доступа
        |--------------------------------------------------------------------------
        */
        $router->get('connect', function () {
            return new Response('You crazy', 200);
        });
    }
);
