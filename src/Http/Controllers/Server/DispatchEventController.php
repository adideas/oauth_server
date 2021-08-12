<?php

namespace Adideas\OauthServer\Http\Controllers\Server;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

class DispatchEventController
{
    public function store(Request $request)
    {
        if ($request->has('model')) {
            try {
                $event = $request->input('event', null);
                $class = $request->input('model', null);
                if (!class_exists($class)) {
                    throw new \Exception('Not eloquent');
                }
                $model_id = $request->input('model_id', null);
                $model = $class::find($model_id);
                if (!($event && $class && $model_id && $model)) {
                    throw new \Exception('Not eloquent');
                }
                Event::dispatch("eloquent.$event: $class", [$model]);

                return new Response('OK', 200);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 422);
            }
        }

        if ($request->has('event')) {
            try {
                $event = $request->input('event', null);
                if (!$event) {
                    throw new \Exception('Not event');
                }
                Event::dispatch($event);

                return new Response('OK', 200);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 422);
            }
        }

        return new Response('Not Allowed', 422);
    }
}
