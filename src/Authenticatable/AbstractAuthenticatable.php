<?php

namespace Adideas\OauthServer\Authenticatable;

abstract class AbstractAuthenticatable
{
    protected array $original = [];
    protected array $auth = [];
    protected $user_id;

    public function __construct(array $data)
    {
        foreach ($data as $name => $value) {
            $this->original[$name] = $value;
        }

        $this->auth = $this->original['auth'] ?? [];
        $this->user_id = $this->original['user_id'] ?? null;
    }

    public function auth($name = null)
    {
        try {
            return $name ? $this->original['auth'][$name] : $this->original['auth'];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function is_fake() {
        return $this->original['fake'] ?? false;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        } else {
            if (strpos($name, 'auth') === 0) {
                return $this->auth(strtolower(str_replace('auth','', $name)));
            }
        }

        return null;
    }

    public function __get(string $name)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        if (isset($this->original[$name])) {
            return $this->original[$name];
        }

        return null;
    }

    public function __set(string $name, $value)
    {
        $this->{$name} = $value;
    }

    public function __isset(string $name)
    {
        return isset($this->{$name}) || method_exists($this, $name);
    }

    public function __unset(string $name)
    {
        unset($this->{$name});
    }

    public function accountId(...$p)
    {
        throw new \Exception('Это не пользователь');
    }

    public function account_id(...$p)
    {
        throw new \Exception('Это не пользователь');
    }

    public function account(...$p)
    {
        throw new \Exception('Это не пользователь');
    }

    public function save(...$p)
    {
        throw new \Exception('Это не пользователь');
    }

    public function update(...$p)
    {
        throw new \Exception('Это не пользователь');
    }

    public function create(...$p)
    {
        throw new \Exception('Это не пользователь');
    }

    public function insert(...$p)
    {
        throw new \Exception('Это не пользователь');
    }
}
