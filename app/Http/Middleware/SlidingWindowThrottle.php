<?php

namespace App\Http\Middleware;

use App\Support\RedisScript;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Middleware\ThrottleRequests;

class SlidingWindowThrottle extends ThrottleRequests
{
    protected function handleRequest($request, Closure $next, array $limits)
    {
        $limits = array_values($limits);
        $now = microtime(true);

        // Hash tag: every key for this request lands in the same Redis Cluster slot.
        $tag = '{'.$limits[0]->key.'}';

        $keys = [];
        $args = [$now];

        foreach ($limits as $limit) {
            $window = intdiv((int) $now, $limit->decaySeconds);
            $base = "rl:{$tag}:{$limit->key}:";

            array_push($keys, $base.$window, $base.($window - 1));
            array_push($args, $limit->maxAttempts, $limit->decaySeconds);
        }

        try {
            $result = RedisScript::named('sliding_window')->run($keys, $args);
        } catch (\Throwable $e) {
            report($e);

            return $next($request); // fail open: a Redis outage shouldn't take the API down
        }

        // Rejected: {0, failed limit index, retry after}. Allowed: {1, remaining per limit...}
        if ((int) $result[0] === 0) {
            $limit = $limits[$result[1] - 1];
            $headers = $this->getHeaders($limit->maxAttempts, 0, (int) $result[2]);

            throw is_callable($limit->responseCallback)
                ? new HttpResponseException(($limit->responseCallback)($request, $headers))
                : new ThrottleRequestsException('Too Many Attempts.', null, $headers);
        }

        $response = $next($request);

        // Laravel keeps the lowest "remaining" across limits in the headers.
        foreach ($limits as $i => $limit) {
            $response = $this->addHeaders($response, $limit->maxAttempts, (int) $result[$i + 1]);
        }

        return $response;
    }
}
