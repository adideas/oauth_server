<?php

namespace Adideas\OauthServer\Classes\HttpClient;

class Client
{
    /*
    |--------------------------------------------------------------------------
    | Client
    |--------------------------------------------------------------------------
    |
    | Это обертка curl для секретного общение между серверами
    |
    |--------------------------------------------------------------------------
     */

    private        $curl;

    private array  $post    = [];

    private array  $headers = [];

    private array  $query   = [];

    private string $url     = '';

    public function __construct(string $url)
    {
        $this->url  = $url;
        $this->curl = curl_init($url);
    }

    public function post(array $form): Client
    {
        foreach ($form as $key => $value) {
            $this->setPostField($key, $value);
        }

        return $this;
    }

    public function setPostField(string $key, $value): Client
    {
        $value            = strval($value);
        $this->post[$key] = $value;

        return $this;
    }

    public function query(array $query)
    {
        $this->query = $query;

        return $this;
    }

    public function headers(array $headers): Client
    {
        $this->headers = $headers;

        return $this;
    }

    public function run(): Response
    {
        $option = [];

        // https 2
        $option[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        $option[CURLOPT_VERBOSE] = false;
        $option[CURLOPT_RETURNTRANSFER] = true;

        if ($this->query) {
            $option[CURLOPT_URL] = explode('?', $this->url)[0] . '?' . (http_build_query($this->query));
        }

        if ($this->post) {
            $option[CURLOPT_POST]           = true;
            $option[CURLOPT_POSTFIELDS]     = $this->post;
        }

        if ($this->headers) {
            $option[CURLOPT_HTTPHEADER] = $this->headers;
        }

        $this->headers = [];
        $this->post    = [];
        $this->query   = [];

        curl_setopt_array($this->curl, $option);

        $response = curl_exec($this->curl);

        return new Response(curl_getinfo($this->curl)['http_code'], $response);
    }
}
