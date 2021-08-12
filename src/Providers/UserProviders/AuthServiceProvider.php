<?php

namespace Adideas\OauthServer\Providers\UserProviders;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class AuthServiceProvider implements UserProvider
{
    public UserProvider $provider;

    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    public function retrieveById($identifier)
    {
        return $this->provider->retrieveById($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        return $this->provider->retrieveByToken($identifier, $token);
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $this->provider->updateRememberToken($user, $token);
    }

    public function retrieveByCredentials(array $credentials)
    {
        return $this->provider->retrieveByCredentials($credentials);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $this->provider->validateCredentials($user, $credentials);
    }
}
