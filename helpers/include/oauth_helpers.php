<?php
use Adideas\OauthServer\Providers\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

if (!function_exists('oauthFireEvent')) {
    function oauthFireEvent(string $event, $model, $payload = null)
    {
        if ($model instanceof Model) {
            $model_str = get_class($model);
            if (!$payload) {
                $payload = $model;
            }
            if (!ServiceProvider::$events) {
                Event::dispatch("eloquent.$event: $model_str", $payload);
                return true;
            } else {
                foreach (ServiceProvider::$events["eloquent.$event: $model_str"] ?? [] as $closure) {
                    $closure("eloquent.$event: $model_str", $payload);
                }
                return true;
            }
        } else {
            if (is_string($model)) {
                if (!ServiceProvider::$events) {
                    if ($payload) {
                        Event::dispatch("eloquent.$event: $model", $payload);
                    } else {
                        Event::dispatch("eloquent.$event: $model");
                    }
                    return true;
                } else {
                    foreach (ServiceProvider::$events["eloquent.$event: $model"] ?? [] as $closure) {
                        if ($payload) {
                            $closure("eloquent.$event: $model", $payload);
                        } else {
                            $closure("eloquent.$event: $model");
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('oauthClient'))
{
    function oauthClient($server) {
        return new \Adideas\OauthServer\Classes\HttpClient\ConnectServer($server);
    }
}
