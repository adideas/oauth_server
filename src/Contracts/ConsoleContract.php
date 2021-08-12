<?php

namespace Adideas\OauthServer\Contracts;

use Illuminate\Contracts\Console\Kernel;

interface ConsoleContract
{
    public function handle() : void;
    public function prompt() : bool;
}
