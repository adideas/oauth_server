<?php

namespace Adideas\OauthServer\Contracts;

interface ServiceProviderContract
{
    public function boot(): void;
    public function register() : void;
    public function runningInConsole() : bool;
}
