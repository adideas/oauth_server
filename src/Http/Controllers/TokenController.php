<?php

namespace Adideas\OauthServer\Http\Controllers;

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Adideas\OauthServer\Classes\HttpClient\Client;
use Adideas\OauthServer\Classes\JWT\JWTHandler;
use Adideas\OauthServer\Contracts\TokenControllerContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller implements TokenControllerContract
{
    /*
    |--------------------------------------------------------------------------
    | Получение ключей авторизации
    |--------------------------------------------------------------------------
    */

    public function token(Request $request): Response
    {
        dd(JWTHandler::encodeServer('app'), 123);
        //dd(oauthClient('app')->connect());
        try {
            $request->validate(['username' => ['required'], 'password' => ['required','min:6']]);

            if ($this->configs['url_oauth_server']) {
                return $this->authRemote($request);
            } else {
                return $this->authLocal($request);
            }

        } catch (ValidationException $validationException) {
            return new Response(['errors' => $validationException->validator->errors()->messages()], $validationException->status);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Боевой режим авторизация через другой сервер
    |--------------------------------------------------------------------------
    */

    private function authRemote(Request $request): Response
    {
        $data = $request->only(['username', 'password']);

        $headers = json_encode($request->headers->all());

        $data['ip_address'] = $request->ip();
        $data['headers']    = $headers;

        $content = (new Client($this->configs['url_oauth_server']))
            ->post($data)
            ->run();

        return new Response($content->toJSON(), $content->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Имитация для локальных машин, WITHOUT REFRESH
    |--------------------------------------------------------------------------
    | Включена поддержка laravel passport для упрощения работы
    |--------------------------------------------------------------------------
    */

    private function authLocal(Request $request): Response
    {
        $auth = Helpers::getAuthDataFromLocalAuth($request);

        if (!$auth) {
            return Helpers::badRequest();
        }

        $model = \Closure::bind(
            function () use ($auth) {
                if ($user = Helpers::reflectFunction($this, 'findAndValidateForPassport', $auth)) {
                    if (!($user instanceof Authenticatable)) {
                        return null;
                    }

                    return $user;
                }
                if (!($user = Helpers::reflectFunction($this, 'findForPassport', $auth))) {
                    if (!($user = $this->where('email', $auth['username'])->first())) {
                        return null;
                    }
                }

                if (!($user instanceof Authenticatable)) {
                    return null;
                }

                $auth['model'] = $user;

                if (($value = !!Helpers::reflectFunction($this, 'validateForPassportPasswordGrant', $auth)) !== null) {
                    if (!Hash::check($auth['password'], $user->getAuthPassword())) {
                        return null;
                    }
                } else {
                    if ($value !== true) {
                        return null;
                    }
                }

                return $user;
            },
            Auth::createUserProvider(Config::get('auth.guards.api.provider'))->createModel()
        )();

        if (!$model) {
            return Helpers::badRequest();
        }

        return JWTHandler::encode(['app'], ['crm'], $model->id);
    }

    /*
    |--------------------------------------------------------------------------
    | REFRESH только на удаленном сервере авторизации
    |--------------------------------------------------------------------------
    */

    public function refresh(Request $request): Response
    {
        try {
            $request->validate(['refresh_token' => ['required']]);

            if ($this->configs['url_oauth_server']) {
                $data = $request->only(['refresh_token']);

                $headers = json_encode($request->headers->all());

                $data['ip_address'] = $request->ip();
                $data['headers']    = $headers;

                $content = (new Client($this->configs['url_oauth_server']))
                    ->post($data)
                    ->run();

                return new Response($content->toJSON(), $content->status);
            }

            return new Response(
                [
                    'errors' => ['refresh_token' => 'not token'],
                ], 401
            );

        } catch (ValidationException $validationException) {
            return new Response(['errors' => $validationException->validator->errors()->messages()], $validationException->status);
        }
    }
}
