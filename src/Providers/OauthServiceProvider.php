<?php

namespace Adideas\OauthServer\Providers;

use Adideas\OauthServer\Providers\UserProviders\ClientServiceProvider;
use Adideas\OauthServer\Providers\UserProviders\ServerServiceProvider;
use Adideas\OauthServer\Providers\UserProviders\AuthServiceProvider;
use Illuminate\Auth\AuthManager;
use Adideas\OauthServer\Contracts\ServiceProviderContract;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
use Adideas\OauthServer\Guard\OauthGuard;

class OauthServiceProvider extends ServiceProvider implements ServiceProviderContract
{
    /*
    |----------------------------------------------------------------------------------
    | Boot
    |----------------------------------------------------------------------------------
     */
    public function boot(): void
    {
        parent::boot();
        $this->registerAuthManager();
    }

    /*
    |----------------------------------------------------------------------------------
    | Register Provider
    |----------------------------------------------------------------------------------
     */
    public function register(): void
    {
        parent::register();
    }

    /*
    |----------------------------------------------------------------------------------
    | Register Auth Manager
    |----------------------------------------------------------------------------------
    | Метод получения Auth Manager.
    | Выполнить Closure когда фасад будет решен.
    |
    | Такой подход намного лучше чем $this->app['auth'] или $this->app->make('auth'),
    | потому что отдаст Auth Manager только тогда когда поставщик auth будет готов.
    |
    | https://laravel.com/api/5.8/Illuminate/Support/Facades/Auth.html#method_resolved
    |
    | Замена: $this->>registerDriver($this->app[auth]);
    |----------------------------------------------------------------------------------
     */
    protected function registerAuthManager(): void
    {
        Auth::resolved(function (AuthManager $authManager) {
            $this->registerDriver($authManager);
        });
    }

    /*
    |----------------------------------------------------------------------------------
    | Register Driver
    |----------------------------------------------------------------------------------
    | После получения Auth Manager необходимо зарегистрировать драйвер который будет
    | использоваться контейнером Laravel для передачи авторизации охраннику Gate.
    |
    | В данном случае регистрация пользовательского драйвера для работы с охранником Guard
    |----------------------------------------------------------------------------------
     */
    protected function registerDriver(AuthManager $authManager): void
    {
        $authManager->extend('oauth', function ($app, string $guard_name, array $guard) {
            $this->registerUserProvider($guard_name);
            return $this->registerGuard($guard);
        });
    }

    /*
    |----------------------------------------------------------------------------------
    | Register Guard
    |----------------------------------------------------------------------------------
    | 1) Вызов создания Guard
    | 2) передача охранника Guard драйверу Auth Manager
    | 3) Обновление экземпляра Requests для всего приложения
    |
    | Защита организуется за счет Requests он передает охраннику информацию о клиенте
    |
    |----------------------------------------------------------------------------------
     */
    protected function registerGuard(array $guard): Guard
    {
        return tap($this->makeGuard($guard), function (Guard $guard) {
            $this->refreshRequest($guard);
        });
    }

    /*
    |----------------------------------------------------------------------------------
    | Make Guard
    |----------------------------------------------------------------------------------
    | В защитника Guard передается текущий Requests полученный из контейнера
    | И передается провайдер пользователей который реализует работу с пользователями
    |
    | В провайдера пользователя передается UserProvider полученный через фасад Auth
    | и сами ключи указанные в config/auth.php
    |
    |----------------------------------------------------------------------------------
     */
    protected function makeGuard(array $guard)
    {
        return new OauthGuard(
            $this->app['request'],
            new AuthServiceProvider(Auth::createUserProvider($guard['provider'])),
            $guard['provider'] ?? ''
        );
    }

    /*
    |----------------------------------------------------------------------------------
    | Refresh request
    |----------------------------------------------------------------------------------
    | Вызов метода контейнера для обновления поставщика и передача нового охранника Guard
    |----------------------------------------------------------------------------------
     */
    protected function refreshRequest(Guard $guard): void
    {
        $this->app->refresh('request', $guard, 'setRequest');
    }

    protected function registerUserProvider(string $guard_name) {
        switch ($guard_name) {
            case 'client':
                $this->withoutModelEvents();
                Auth::provider('client', function($app, array $config) {
                    return new ClientServiceProvider($app->make('request'));
                });
                break;
            case 'server':
                $this->withoutModelEvents();
                Auth::provider('server', function($app, array $config) {
                    return new ServerServiceProvider($app->make('request'));
                });
                break;
        }
    }
}
