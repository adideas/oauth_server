<?php

namespace Adideas\OauthServer\Http\Controllers\Server;

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class FindUserController
{
    public function index(Request $request)
    {
        try {
            $request->validate(['username' => ['required']]);
            if (!($name_server = Config::get('oauth.server', null))) {
                throw new \Exception('Error');
            }
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

                    return $user;
                },
                Auth::createUserProvider(Config::get('auth.guards.api.provider'))->createModel()
            )();

            if (!$model) {
                return Helpers::badRequest();
            }

            return new Response(
                [
                    'user_id'   => $model->id,
                    'signature' => Helpers::encrypt($model->password),
                    'server'    => $name_server,
                ], 200
            );
        } catch (ValidationException $validationException) {
            return new Response(
                ['errors' => $validationException->validator->errors()->messages()],
                $validationException->status
            );
        } catch (\Exception $e) {
            return new Response(['errors' => $e->getMessage()], 404);
        }

        return new Response(['errors' => 'NotFoundPage'], 404);
    }
}
