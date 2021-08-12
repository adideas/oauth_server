<?php

namespace Adideas\OauthServer\Authenticatable;

use Illuminate\Contracts\Auth\Authenticatable;

class ClientAuthenticatable extends AbstractAuthenticatable implements Authenticatable
{
    public function getAuthIdentifierName(): string
    {
        return isset($this->identifier) ? $this->identifier : '';
    }

    public function getAuthIdentifier()
    {
        return isset($this->identifier) ? $this->identifier : '';
    }

    public function getAuthPassword(): string
    {
        throw new \Exception('Пароль запрещен');
    }

    public function getRememberToken(): string
    {
        throw new \Exception('Токен запрещен');
    }

    public function setRememberToken($value): void
    {
        throw new \Exception('Токен запрещен');
    }

    public function getRememberTokenName(): string
    {
        throw new \Exception('Токен запрещен');
    }
}
