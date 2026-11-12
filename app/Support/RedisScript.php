<?php

namespace App\Support;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

/**
 * Runs a Lua script stored in resources/lua/{name}.lua.
 *
 *   RedisScript::named('sliding_window')->run($keys, $args);
 *
 * Sends only the script's SHA1 (EVALSHA). If Redis doesn't have the script cached
 * (first call, restart, failover), it sends the full script once with EVAL.
 *
 * Written for phpredis, Laravel's default Redis client.
 */
final class RedisScript
{
    /** @var array<string, array{0: string, 1: string}> source and sha1, per script name */
    private static array $loaded = [];

    private function __construct(
        private readonly string $source,
        private readonly string $sha,
    ) {}

    public static function named(string $name): self
    {
        self::$loaded[$name] ??= self::load($name);

        return new self(...self::$loaded[$name]);
    }

    /**
     * @param  array<int, string>  $keys
     * @param  array<int, mixed>  $args
     */
    public function run(array $keys, array $args = [], ?Connection $connection = null): mixed
    {
        $connection ??= Redis::connection();
        $parameters = [...$keys, ...$args];

        $result = $connection->command('evalsha', [$this->sha, $parameters, count($keys)]);

        if ($result === false && $this->lastErrorStartsWith($connection, 'NOSCRIPT')) {
            $connection->client()->clearLastError();

            $result = $connection->command('eval', [$this->source, $parameters, count($keys)]);
        }

        // phpredis returns false for script errors instead of throwing. Turn that into an exception.
        if ($result === false && ($error = $connection->client()->getLastError()) !== null) {
            $connection->client()->clearLastError();

            throw new RuntimeException("Redis script failed: {$error}");
        }

        return $result;
    }

    private static function load(string $name): array
    {
        $path = resource_path("lua/{$name}.lua");
        $source = @file_get_contents($path);

        if ($source === false) {
            throw new RuntimeException("Redis script not found: {$path}");
        }

        return [$source, sha1($source)];
    }

    private function lastErrorStartsWith(Connection $connection, string $prefix): bool
    {
        return str_starts_with((string) $connection->client()->getLastError(), $prefix);
    }
}
