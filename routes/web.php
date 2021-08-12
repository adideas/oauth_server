<?php

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Adideas\OauthServer\Http\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'prefix'    => 'oauth',
        'namespace' => Helpers::ROOT_NAMESPACE(['Http', 'Controllers']),
    ],
    function ($router) {
        /*
        |--------------------------------------------------------------------------
        | регистрация маршрута авторизации
        |--------------------------------------------------------------------------
        */
        $router->post(
            '/token',
            [
                'as'         => 'oauth.token',
                'uses'       => 'TokenController@token',
                'middleware' => [ThrottleRequests::class],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | регистрация маршрута восстановления
        |--------------------------------------------------------------------------
        */
        $router->post(
            '/token/refresh',
            [
                'as'         => 'oauth.token.refresh',
                'uses'       => 'TokenController@refresh',
                'middleware' => ['web', 'auth', ThrottleRequests::class],
            ]
        );
    }
);




