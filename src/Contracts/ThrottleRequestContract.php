<?php

namespace Adideas\OauthServer\Contracts;

use \Closure;

interface ThrottleRequestContract
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '');
}
