<?php

namespace Adideas\OauthServer\Classes\HttpClient;

use Adideas\OauthServer\Classes\JWT\JWTHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Class ConnectServer
 * @package Adideas\OauthServer\Classes\HttpClient
 */
class ConnectServer
{
    private string $server = '';
    private string $url = '';
    protected array $route = ['server'];
    protected array $form = [];
    protected $bearer;


    public function __construct($server, $url = null)
    {
        if (!$url) {
            if (!($this->url = Config::get('oauth.servers.' . $server, null))) {
                throw new \Exception('Check config/oauth.php -> servers -> '. $server);
            }
        } else {
            $this->url = $url;
        }

        $this->server = $server;
        $this->bearer = Cache::remember(__CLASS__ . 'token_bearer',80000, function () {
            try {
                return JWTHandler::encodeServer($this->server)->original['access_token'];
            } catch (\Exception $e) {
                return null;
            }
        });

        if (!$this->bearer) {
            Cache::forget(__CLASS__ . 'token_bearer');
            throw new \Exception('Нет токена');
        }
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }
        array_push($this->route, $name);
        return $this;
    }

    public function route(string $url = '') : ConnectServer
    {
        if (!$url) {
            array_push($this->route, 'route');
        } else {
            $this->route = ['server', ...explode('/', $url)];
        }

        return $this;
    }

    public function form(array $form = []) : ConnectServer
    {
        if ($form) {
            $this->form = $form;
        }
        return $this;
    }

    public function post(array $form = []) : Response
    {
        if ($form) {
            $this->form = $form;
        }
        return (new Client(implode('/', [$this->url, ...$this->route])))
            ->headers(['Authorization: Bearer ' . $this->bearer])
            ->post($this->form)
            ->run();
    }

    public function get(array $query = []) : Response
    {
        return (new Client(implode('/', [$this->url, ...$this->route])))
            ->headers(['Authorization: Bearer ' . $this->bearer])
            ->query($query)
            ->run();
    }

    public function connect() : Response
    {
        return (new Client(implode('/', [$this->url, 'server', 'connect'])))
            ->headers(['Authorization: Bearer ' . $this->bearer])
            ->run();
    }
}
