<?php

namespace Adideas\OauthServer\Http\Controllers;

use Illuminate\Support\Facades\Config;

abstract class Controller
{
    protected array $configs;

    public function __construct()
    {
        $this->configs = Config::get('oauth');
    }
}
