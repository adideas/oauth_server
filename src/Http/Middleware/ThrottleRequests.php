<?php

namespace Adideas\OauthServer\Http\Middleware;

use Adideas\OauthServer\Contracts\ThrottleRequestContract;
use Illuminate\Cache\RateLimiter;
use Closure;
use Illuminate\Http\Response;

class ThrottleRequests implements ThrottleRequestContract
{
    /*
    |--------------------------------------------------------------------------
    | ThrottleRequests
    |--------------------------------------------------------------------------
    */

    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next, $maxAttempts = 6, $decayMinutes = 1, $prefix = '')
    {
        $decayMinutes = $decayMinutes * 60;

        // Получить идентификатор клиента
        $identifier = $prefix.$this->resolveRequestSignature($request);

        // Проверка на максимальное количество запросов
        if ($this->limiter->tooManyAttempts($identifier, $maxAttempts)) {
            // Превышение количества запросов
            return $this->buildMaxAttemptResponse($identifier, $maxAttempts);
        }

        // Через сколько очистить информацию о клиента
        $this->limiter->hit($identifier, $decayMinutes);

        $response = $next($request);

        // Отдаем положительный ответ
        return $this->buildResponse($response, $identifier, $maxAttempts);
    }

    /*
    |--------------------------------------------------------------------------
    | Получить идентификатор клиента
    |--------------------------------------------------------------------------
    */
    protected function resolveRequestSignature($request): string
    {
        return sha1(
            $request->method() .
            '|' . $request->getHost() .
            '|' . $request->ip()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Сборка положительного ответа
    |--------------------------------------------------------------------------
    */
    protected function buildResponse($response, $identifier, $maxAttempts): Response
    {
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($identifier, $maxAttempts)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Сборка отрицательного ответа
    |--------------------------------------------------------------------------
    */
    protected function buildMaxAttemptResponse($key, $maxAttempts): Response
    {
        $response = new Response('Too Many Attempts.', 429);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $this->limiter->availableIn($key)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Общая функция добавления хедеров
    |--------------------------------------------------------------------------
    */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null): Response
    {
        $headers = ['X-RateLimit-Limit' => $maxAttempts, 'X-RateLimit-Remaining' => $remainingAttempts,];

        if (!is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
        }

        $response->headers->add($headers);

        return $response;
    }

    /*
    |--------------------------------------------------------------------------
    | Рассчитать оставшиеся количество попыток => return [Сколько попыток осталось] int
    |--------------------------------------------------------------------------
    |
    | $identifier - идентификатор клиента {string: sha1}
    | $max_attempts - максимальное количество попыток {int: 60}
    |
    |--------------------------------------------------------------------------
    */
    protected function calculateRemainingAttempts(string $identifier, int $max_attempts): int
    {
        // Сколько попыток запроса уже было
        $number_of_attempts = $this->limiter->attempts($identifier);

        // Добавим текущую попытку запроса
        $number_of_attempts += 1;

        return $max_attempts - $number_of_attempts;
    }
}
