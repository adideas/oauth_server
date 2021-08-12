<?php

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;

Route::group(
    [
        'prefix'    => 'server',
        'namespace' => Helpers::ROOT_NAMESPACE(['Http', 'Controllers', 'Server']),
        'middleware' => ['auth:server', 'bindings']
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
        /*
        |--------------------------------------------------------------------------
        | поиск пользователя
        |--------------------------------------------------------------------------
        */
        $router->post('user', 'FindUserController@index');
        /*
        |--------------------------------------------------------------------------
        | регистрация маршрута событий
        |--------------------------------------------------------------------------
        */
        $router->post('event', 'DispatchEventController@store');
    }
);
