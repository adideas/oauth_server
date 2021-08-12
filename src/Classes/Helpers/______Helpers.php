<?php

namespace Adideas\OauthServer\Classes\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ______Helpers
{
    public static array $ENV = [];

    public static function ROOT_DIR(...$path): string
    {
        if (!isset(self::$ENV['_root_dir'])) {
            $array_path             = explode(DIRECTORY_SEPARATOR, __DIR__);
            $array_path             = array_slice($array_path, 0, count($array_path) - 3);
            self::$ENV['_root_dir'] = implode(DIRECTORY_SEPARATOR, $array_path);
        }

        if ($path && count($path) == 1) {
            $path = $path[0];
        }
        if ($path && is_array($path)) {
            return self::$ENV['_root_dir'] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path);
        }

        return self::$ENV['_root_dir'];
    }

    public static function ROOT_NAMESPACE(...$namespace): string
    {
        return Cache::rememberForever(__CLASS__.__FUNCTION__.json_encode($namespace), function () use ($namespace)  {
            $base = "\Adideas\OauthServer";
            if ($namespace && count($namespace) == 1) {
                $namespace = $namespace[0];
            }

            if ($namespace && is_array($namespace)) {
                return $base . "\\" . implode("\\", $namespace);
            }
            return $base;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Фальшивый приватный ключ для авторизации без сервера авторизации
    |--------------------------------------------------------------------------
    */
    public static function getFakePrivateKey()
    {
        $key = "-----BEGIN RSA PRIVATE KEY-----\r\n";
        $key .= "MIIBOQIBAAJAepfjB5kPXByBBCX1jdtBZRcAxpYavyaYoH8cl2bc92hgZ0Sn68Xg\r\n";
        $key .= "d0pqi492ry6APyl/0AImKdOEboFvKL75tQIDAQABAkAqrnDTd12aoy3j5NdeISTe\r\n";
        $key .= "bijN+vqq7GQdFMQ+jgiGdiwd5WPzuoN+xAjK4VosRjYDbbTnAYuy7SuSTnEKcZih\r\n";
        $key .= "AiEAtW+B4xpZEWq91XEWf3z2zPGOdYUEDFlwNj67wXwDuVkCIQCs+bI10lQfd/6L\r\n";
        $key .= "wiNesN9dTAP8UQBWPPClSoIy//DbvQIhAKmUwptI+iz8Ttib7cJVQ7yEnnmbTRBZ\r\n";
        $key .= "3DbnZchPqI9pAiAC4dY6V1rXe2ReZ8m3FjNilpWqap8a0MEhv/ATcXhN8QIgKZuz\r\n";
        $key .= "H80euDk+EtECgWGMcd2u8l+wu+cUC3JjuChgAJw=\r\n";
        $key .= "-----END RSA PRIVATE KEY-----";
        return $key;
    }

    /*
    |--------------------------------------------------------------------------
    | Фальшивый публичный ключ для авторизации без сервера авторизации
    |--------------------------------------------------------------------------
    */
    public static function getFakePublicKey()
    {
        $key = "-----BEGIN PUBLIC KEY-----\r\n";
        $key .= "MFswDQYJKoZIhvcNAQEBBQADSgAwRwJAepfjB5kPXByBBCX1jdtBZRcAxpYavyaY\r\n";
        $key .= "oH8cl2bc92hgZ0Sn68Xgd0pqi492ry6APyl/0AImKdOEboFvKL75tQIDAQAB\r\n";
        $key .= "-----END PUBLIC KEY-----";
        return $key;
    }

    public static function getPublicKey()
    {
        $key = Cache::remember(__CLASS__.__FUNCTION__.'public_key',80000, function () {
            $name = Config::get('oauth.public_key');
            $path = App::storagePath() . DIRECTORY_SEPARATOR . ($name ? $name : 'oauth-public.key');
            if (file_exists($path)) {
                return file_get_contents($path);
            }

            return null;
        });

        if (!$key) {
            Cache::forget(__CLASS__.__FUNCTION__.'public_key');
            throw new \Exception('Нет публичного ключа');
        }
        return $key;
    }

    public static function getAuthDataFromLocalAuth(Request $request):array
    {
        $username = $request->input('username','');
        $password = $request->input('password', '');
        $username = str_replace([' ', '\t', '\n', '\r', '\0', '\x0B', '+', '(', ')'], '', $username);
        if ($username) {
            $email = $username;
            $phone    = [preg_replace('/[^0-9]/', '', $username)];
            if ($username == $phone[0]) {
                if (strlen($phone[0]) == 11 && ($phone[0][0] == '8' || $phone[0][0] == 8)) {
                    array_push($phone, $phone[0]);
                    $phone[1][0] = 7;
                }
                $email = '';
            } else {
                $phone = [];
            }
            return compact('username','phone','password','email');
        }
        return [];
    }

    public static function reflectFunction($context, $name, array $args = [])
    {
        if (!method_exists($context, $name)) {
            return null;
        }
        $class = new \ReflectionClass($context);
        $function = $class->getMethod($name);
        $parameters = $function->getParameters();
        $_args = [];
        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                array_push($args, $args);
                continue;
            }
            if (isset($args[$parameter->name])) {
                array_push($_args, $args[$parameter->name]);
                continue;
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    array_push($_args, $parameter->getDefaultValue());
                }
            }
        }

        try {
         return $function->getClosure($context)(...$_args);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function badRequest() {
        return new Response('Фамилия имя отчество ты хуйлуша?', 401);
    }

    public static function encrypt($data)
    {
        try {
            $data = json_encode($data);
            $options = [$data, "AES-256-CBC", self::getPublicKey(), OPENSSL_RAW_DATA, openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-256-CBC"))];
            $ciphertext_raw = openssl_encrypt(...$options);
            return base64_encode($options[4].hash_hmac('sha256', $ciphertext_raw, $options[2], true).$ciphertext_raw );
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function decrypt($data)
    {
        try {
            $data = base64_decode($data);
            $key = self::getPublicKey();
            $iv_length = openssl_cipher_iv_length("AES-256-CBC");
            $ciphertext_raw = substr($data, $iv_length + 32);
            $original = openssl_decrypt($ciphertext_raw, "AES-256-CBC", $key, OPENSSL_RAW_DATA, substr($data, 0, $iv_length));
            if (hash_equals(substr($data, $iv_length, 32), hash_hmac('sha256', $ciphertext_raw, $key, true))) {
                return json_decode($original);
            }
        } catch (\Exception $e) {
            //
        }

        return null;
    }
}
