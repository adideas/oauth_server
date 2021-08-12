<?php

namespace Adideas\OauthServer\Guard;

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Adideas\OauthServer\Classes\JWT\JWTHandler;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Macroable;

class OauthGuard implements Guard
{
    use GuardHelpers, Macroable;

    protected Request $request;
    protected string $guard_name;

    public function __construct(Request $request, UserProvider $provider, string $guard_name = '')
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->guard_name = $guard_name;
    }

    private function error() {
        throw new HttpResponseException(Helpers::badRequest());
    }

    public function user()
    {
        $token = $this->request->bearerToken();

        if (Cache::has('OauthGuardLogout' . $token)) {
            return $this->error();
        }

        if (!$token) {
            return $this->error();
        }

        if ($this->guard_name == 'server' || $this->guard_name == 'client') {
            if ($route = $this->request->route()) {
                if (isset($route->action['namespace'])) {

                    $namespace = 'Http\\Controllers\\'.ucfirst($this->guard_name);

                    if (count(explode($namespace,$route->action['namespace'])) != 2) {
                        return $this->error();
                    }
                } else {
                    return $this->error();
                }
            }
        }

        if (isset($this->guard_name) && $this->guard_name == 'server') {
            $token = JWTHandler::token($token, 'HS512');
        } else {
            $token = JWTHandler::token($token);
        }
        if (!is_array($token)) {
            throw new HttpResponseException($token);
        }

        if (get_class($this->provider->provider) == EloquentUserProvider::class) {
            if (isset($token['user_id'])) {
                return $this->provider->retrieveById($token['user_id']);
            } else {
                $this->error();
            }
        } else {
            return $this->provider->retrieveById($token);
        }


        return $this->error();
    }

    public function validate(array $credentials = [])
    {
        return !is_null((new static($credentials['request'], $this->getProvider()))->user());
    }

    // логика получения id модели
    public function id()
    {
        if ($this->user() instanceof Model) {
            return $this->user()->getAuthIdentifier();
        }

        return 0;
    }

    // логика установки пользователя
    public function setUser(Authenticatable $user)
    {
        throw new \Exception('Запрещено устанавливать пользователя');
    }

    public function setProvider(UserProvider $provider)
    {
        throw new \Exception('Запрещено устанавливать провайдер');
    }

    public function viaRemember(...$p) {
        throw new \Exception('Запрещено идентифицировать пользователя из CLI');
    }

    public function login(...$p)
    {
        throw new \Exception('Запрещено идентифицировать пользователя из CLI');
    }

    public function loginUsingId(...$p)
    {
        throw new \Exception('Запрещено идентифицировать пользователя из CLI');
    }

    public function get(...$p)
    {
        throw new \Exception('Запрещено идентифицировать пользователя из CLI');
    }

    public function resolved(...$p)
    {
        throw new \Exception('Запрещено идентифицировать пользователя из CLI');
    }

    public function logout() {
        Cache::forever('OauthGuardLogout' . App::make('request')->bearerToken(), 'logout');
    }
}
