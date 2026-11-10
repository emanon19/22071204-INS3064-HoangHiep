<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class RateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;

    public function __construct(
        private string $directory
    ) {
        if (
            !is_dir($this->directory) &&
            !mkdir($this->directory, 0700, true) &&
            !is_dir($this->directory)
        ) {
            throw new RuntimeException(
                'Unable to initialize login rate limiter.'
            );
        }
    }

    public function remainingSeconds(string $ip): int
    {
        return $this->withLockedData(
            $ip,
            function (array &$data): int {
                $now = time();

                $lockedUntil = (int) ($data['locked_until'] ?? 0);

                if ($lockedUntil > $now) {
                    return $lockedUntil - $now;
                }

                $data['locked_until'] = 0;
                $data['attempts'] = $this->validAttempts(
                    $data['attempts'] ?? [],
                    $now
                );

                return 0;
            }
        );
    }

    public function recordFailure(string $ip): int
    {
        return $this->withLockedData(
            $ip,
            function (array &$data): int {
                $now = time();

                $lockedUntil = (int) ($data['locked_until'] ?? 0);

                if ($lockedUntil > $now) {
                    return $lockedUntil - $now;
                }

                $attempts = $this->validAttempts(
                    $data['attempts'] ?? [],
                    $now
                );

                $attempts[] = $now;

                $data['attempts'] = $attempts;

                if (count($attempts) >= self::MAX_ATTEMPTS) {
                    $data['locked_until'] = $now + self::WINDOW_SECONDS;

                    return self::WINDOW_SECONDS;
                }

                return 0;
            }
        );
    }

    public function clear(string $ip): void
    {
        $this->withLockedData(
            $ip,
            function (array &$data): void {
                $data = [
                    'attempts' => [],
                    'locked_until' => 0,
                ];
            }
        );
    }

    private function validAttempts(mixed $attempts, int $now): array
    {
        if (!is_array($attempts)) {
            return [];
        }

        return array_values(array_filter(
            $attempts,
            static fn ($timestamp): bool =>
                is_int($timestamp) &&
                $timestamp > ($now - self::WINDOW_SECONDS) &&
                $timestamp <= $now
        ));
    }

    private function withLockedData(string $ip, callable $callback): mixed
    {
        $filename = hash('sha256', $ip) . '.json';
        $path = $this->directory . DIRECTORY_SEPARATOR . $filename;

        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException(
                'Unable to access login rate limiter storage.'
            );
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException(
                    'Unable to lock login rate limiter storage.'
                );
            }

            rewind($handle);
            $contents = stream_get_contents($handle);

            $data = [];

            if (is_string($contents) && $contents !== '') {
                $decoded = json_decode($contents, true);

                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }

            $result = $callback($data);

            rewind($handle);
            ftruncate($handle, 0);

            $json = json_encode($data, JSON_THROW_ON_ERROR);

            if (fwrite($handle, $json) === false) {
                throw new RuntimeException(
                    'Unable to write login rate limiter storage.'
                );
            }

            fflush($handle);
            chmod($path, 0600);

            flock($handle, LOCK_UN);

            return $result;
        } finally {
            fclose($handle);
        }
    }
}