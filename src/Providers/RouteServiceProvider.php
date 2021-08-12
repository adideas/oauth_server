<?php

namespace Adideas\OauthServer\Providers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\ServiceProvider;
use Closure;

class RouteServiceProvider extends ServiceProvider
{
    use ForwardsCalls;

    protected $namespace = 'App\Http\Controllers';

    protected $loadRoutesUsing;

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->booted(
            function () {
                $this->setRootControllerNamespace();

                if ($this->routesAreCached()) {
                    $this->loadCachedRoutes();
                } else {
                    $this->loadRoutes();

                    $this->app->booted(
                        function () {
                            $this->app['router']->getRoutes()->refreshNameLookups();
                            $this->app['router']->getRoutes()->refreshActionLookups();
                        }
                    );
                }
            }
        );
    }

    protected function routes(Closure $routesCallback)
    {
        $this->loadRoutesUsing = $routesCallback;

        return $this;
    }

    protected function setRootControllerNamespace()
    {
        if (!is_null($this->namespace)) {
            $this->app[UrlGenerator::class]->setRootControllerNamespace($this->namespace);
        }
    }

    protected function routesAreCached()
    {
        return $this->app->routesAreCached();
    }

    protected function loadCachedRoutes()
    {
        $this->app->booted(
            function () {
                require $this->app->getCachedRoutesPath();
            }
        );
    }

    protected function loadRoutes()
    {
        if (!is_null($this->loadRoutesUsing)) {
            $this->app->call($this->loadRoutesUsing);
        } elseif (method_exists($this, 'map')) {
            $this->app->call([$this, 'map']);
        }
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->app->make(Router::class), $method, $parameters
        );
    }

    public function map()
    {
        $this->registerClientRoutes();
        $this->registerServerRoutes();
    }

    public function registerClientRoutes()
    {
        $path = $this->app->basePath('routes/client.php');
        if (file_exists($path)) {
            Route::prefix('client')
                ->middleware(['auth:client', 'bindings'])
                ->namespace('App\Http\Controllers\Client')
                ->group($path);
        }
    }

    public function registerServerRoutes()
    {
        $path = $this->app->basePath('routes/server.php');
        if (file_exists($path)) {
            Route::prefix('server')
                ->middleware(['auth:server', 'bindings'])
                ->namespace('App\Http\Controllers\Server')
                ->group($path);
        }
    }
}
