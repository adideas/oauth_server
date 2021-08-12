<?php

namespace Adideas\OauthServer\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface TokenControllerContract
{
    public function token(Request $request): Response;
    public function refresh(Request $request): Response;
}
