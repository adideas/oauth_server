<?php

namespace Adideas\OauthServer\Classes\JWT;

use Adideas\OauthServer\Classes\Helpers\______Helpers as Helpers;
use Firebase\JWT\JWT;
use Illuminate\Http\Response;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;

class JWTHandler
{
    public string $token;

    public array  $token_array = [];

    public array  $alg         = [];

    public static function encode(array $resources, array $guest_resources, int $user_id): Response
    {
        try {
            $env = Config::get('app.env', Env::get('APP_ENV', ''));
            if ($env != 'local') {
                throw new \Exception('No Auth');
            }

            $now = time();

            $def = [
                'jti'  => 'none',
                'nbf'  => $now,
                'iat'  => $now,
                'exp'  => $now + 86400,
                'dom'  => 'localhost',
                'type' => 'user',
                'auf'  => $resources,
                'gst'  => array_unique(array_merge($resources, $guest_resources)),
            ];

            foreach ($resources as $resource) {
                $def["sub[$resource]"] = $user_id;
            }

            $jwt = JWT::encode($def, Helpers::getFakePrivateKey(), 'RS256', null, ['fake' => true]);

            return new Response(
                [
                    'access_token' => $jwt,
                    'expires_in'   => $def['exp'],
                    'token_type'   => 'Bearer',
                    'domain'       => $def['dom'],
                    'auth'         => $def['auf'],
                    'guest'        => $def['gst'],
                ],
                200
            );
        } catch (\Exception $e) {
        }

        return Helpers::badRequest();
    }

    public static function encodeServer(string $server, string $identifier = null): Response
    {
        try {
            if (!$identifier) {
                $identifier = Config::get('oauth.server_identifier', null);
                if (!$identifier) {
                    throw new \Exception('server_identifier');
                }
            }


            $env = Config::get('app.env', Env::get('APP_ENV', ''));

            $public_key = $env == 'local' ? Helpers::getFakePublicKey() : Helpers::getPublicKey();

            $now = time();

            $def = [
                'jti'  => $env == 'local' ? 'none' : $identifier,
                'dom'  => $env == 'local' ? 'localhost' : $identifier,
                'type' => 'server',
                'auf'  => [$server],
                'gst'  => [$server],
                'nbf'  => $now,
                'iat'  => $now,
                'exp'  => $now + 86400,
                'sub' => $identifier
            ];

            $jwt = JWT::encode($def, $public_key, 'HS512', null, $env == 'local' ? ['fake' => true] : []);

            return new Response(
                [
                    'access_token' => $jwt,
                    'expires_in'   => $def['exp'],
                    'token_type'   => 'Bearer',
                    'domain'       => $def['dom'],
                    'auth'         => $def['auf'],
                    'guest'        => $def['gst'],
                ],
                200
            );
        } catch (\Exception $e) {}

        return Helpers::badRequest();
    }

    public static function token(string $token, string $alg = null)
    {
        try {
            $exp = explode('.', $token);
            $jwt = array_replace(
                json_decode(base64_decode($exp[0]), true),
                json_decode(base64_decode($exp[1]), true)
            );

            if ($jwt['exp'] < time()) {
                throw new \Exception('Вышло время использования ключа');
            }

            if ($jwt['exp'] < $jwt['nbf'] || $jwt['exp'] < $jwt['iat']) {
                throw new \Exception('Не правильно собран ключ');
            }

            if (isset($jwt['fake']) && $jwt['dom'] != 'localhost') {
                throw new \Exception('Не правильная сборка тестового ключа');
            }

            if (isset($jwt['fake']) && $jwt['jti'] != 'none') {
                throw new \Exception('Тестовый ключ не может иметь идентификатор');
            }

            $env = Config::get('app.env', Env::get('APP_ENV', ''));

            if (isset($jwt['fake']) && $env == 'local') {
                $public_key = Helpers::getFakePublicKey();
                $alg        = $alg ? $alg : 'RS256';
            } else {
                if (isset($jwt['fake']) || $jwt['dom'] == 'localhost') {
                    throw new \Exception('Не правильная сборка ключа');
                }

                if (isset($jwt['fake']) || $jwt['jti'] == 'none') {
                    throw new \Exception('Ключ должен иметь идентификатор');
                }
                $public_key = Helpers::getPublicKey();
            }

            if (JWT::decode($token, $public_key, $alg ? [$alg] : ['HS512', 'RS512', 'RS256'])) {
                $server_name = Config::get('oauth.server', 'none');

                if ($server_name == 'none' && $env != 'local') {
                    throw new \Exception('No Auth');
                }

                $jwt['auf'] = !!in_array($server_name, $jwt['auf']);
                $jwt['gst'] = !!in_array($server_name, $jwt['gst']);

                if (!($jwt['auf'] || $jwt['gst'])) {
                    throw new \Exception('Нет доступов к текущему серверу');
                }

                if (isset($jwt["sub[$server_name]"])) {
                    $jwt['user_id'] = $jwt["sub[$server_name]"];
                }

                if (isset($jwt['sub']) && $jwt['type'] == 'server') {
                    $jwt['identifier'] = $jwt['sub'];
                } else {
                    preg_match_all('/sub\[(.*?)]/', implode(' ',array_keys($jwt)), $m);
                    if (count($m) == 2) {
                        $jwt['auth'] = [];
                        foreach ($m[0] as $index => $key) {
                            $jwt['auth'][$m[1][$index]] = $jwt[$key];
                            unset($jwt[$key]);
                        }
                    }

                    $jwt['identifier'] = $token;
                }

                return $jwt;
            } else {
                throw new \Exception('Сигнатура не совпала');
            }
        } catch (\Exception $e) {}

        return Helpers::badRequest();
    }
}
