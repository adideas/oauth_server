<?php

namespace Adideas\OauthServer\Classes\HttpClient;

class Response
{
    public $status;
    public $response;

    public function __construct($status, $response)
    {
        $this->status = $status;
        $this->response = $response;
    }

   public function toJSON() : array
   {
       return json_decode($this->response, true) ?? [];
   }
}
