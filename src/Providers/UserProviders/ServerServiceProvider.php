<?php

namespace Adideas\OauthServer\Providers\UserProviders;

use Adideas\OauthServer\Authenticatable\ServerAuthenticatable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class ServerServiceProvider implements UserProvider
{
    public function __construct(Request $request)
    {
    }

    public function retrieveById($identifier)
    {
        return new ServerAuthenticatable($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        dd(__CLASS__, __FUNCTION__, func_get_args());
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        dd(__CLASS__, __FUNCTION__, func_get_args());
    }

    public function retrieveByCredentials(array $credentials)
    {
        dd(__CLASS__, __FUNCTION__, func_get_args());
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return true;
    }
}
