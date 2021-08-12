<?php

namespace Adideas\OauthServer\Providers;

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Adideas\OauthServer\Contracts\ServiceProviderContract;
use Adideas\OauthServer\Console\Install;
use Adideas\OauthServer\Console\Uninstall;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

abstract class ServiceProvider extends SupportServiceProvider implements ServiceProviderContract
{
    public static array $events = [];

    protected array     $configs;

    public function boot(): void
    {
        $this->registerRoutes();
    }

    public function register(): void
    {
        $this->registerConfigs();
    }

    public function runningInConsole(): bool
    {
        return $this->app->runningInConsole();
    }

    /*
    |--------------------------------------------------------------------------
    | Регистрация маршрутов для авторизации через текущего провайдера
    |--------------------------------------------------------------------------
    |
    | Маршруты работают как шлюзы перебрасывания данных на сервер авторизации
    | и есть возможность локальной авторизации без наличия сервера
    | но только локально!!!
    |
    |--------------------------------------------------------------------------
     */

    private function registerRoutes(): void
    {
        $this->loadRoutesFrom(Helpers::ROOT_DIR(['routes', 'web.php']));
        $this->loadRoutesFrom(Helpers::ROOT_DIR(['routes', 'server.php']));
        $this->loadRoutesFrom(Helpers::ROOT_DIR(['routes', 'client.php']));

        if ($this->runningInConsole()) {
            $this->publishes(
                [
                    Helpers::ROOT_DIR(['config']) => $this->app->basePath('config'),
                    Helpers::ROOT_DIR(['routes', 'publish']) => $this->app->basePath('routes'),
                ], 'oauth-files'
            );
        }
    }

    /*
    |----------------------------------------------------------------------------------
    | Register All Config Files
    |----------------------------------------------------------------------------------
    | Регистрация всех конфигурационных файлов
    |----------------------------------------------------------------------------------
     */
    private function registerConfigs(): void
    {
        $this->mergeConfigFrom(Helpers::ROOT_DIR(['config', 'oauth.php']), 'oauth');
    }

    /*
    |----------------------------------------------------------------------------------
    | Forget all events for all models
    |----------------------------------------------------------------------------------
    | Выключение всех событий моделей! работает только в client и server
    | Потому что все используют observer не понимая сути
    |----------------------------------------------------------------------------------
    */
    protected function withoutModelEvents()
    {
        Event::listen(
            'eloquent.booted: *',
            function ($ev, $model) {
                $model  = str_replace('eloquent.booted: ', '', $ev);
                $events =
                    'retrieved.creating.created.updating.updated.saving.saved.deleting.deleted.restoring.restored';
                foreach (explode('.', $events) as $event) {
                    if ($dis = Event::getListeners("eloquent.$event: $model")) {
                        ServiceProvider::$events["eloquent.$event: $model"] = $dis;
                    }
                    Event::forget("eloquent.$event: $model");
                }
            }
        );
        /*Event::listen('OauthServerProviderRunEvents', function () {
            Event::forget('eloquent.booted: *');
            foreach (ServiceProvider::$events as $event => $callbacks) {
                foreach ($callbacks as $callback) {
                    Event::listen($event, function (...$data) use ($event,$callback) {
                        $callback($event, $data);
                    });
                }
            }
        });*/
    }
}
