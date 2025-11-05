<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Throwable;

class OnlineVisitors
{
    /**
     * Имя множества с активными посетителями.
     */
    private const STORAGE_KEY = 'visitors:online';

    /**
     * Префикс ключа с деталями посетителя.
     */
    private const DETAILS_PREFIX = 'visitors:detail:';

    /**
     * Тайм-аут в секундах, после которого посетитель считается оффлайн.
     */
    public const TTL_SECONDS = 120;

    /**
     * Сохраняет/продлевает присутствие посетителя.
     *
     * @param  string  $id
     * @param  array{id?: string,label?: string,initial?: string,color?: ?string,last_seen?: string}  $payload
     */
    public function remember(string $id, array $payload): void
    {
        if ($id === '') {
            return;
        }

        try {
            $this->cleanup();

            $now = Carbon::now();
            $data = [
                'id' => $id,
                'label' => $payload['label'] ?? 'Гость',
                'initial' => $payload['initial'] ?? null,
                'color' => $payload['color'] ?? null,
                'last_seen' => $payload['last_seen'] ?? $now->toIso8601String(),
            ];

            Redis::pipeline(function ($pipe) use ($id, $now, $data) {
                $pipe->zadd(self::STORAGE_KEY, $now->getTimestamp(), $id);
                $pipe->setex(
                    $this->detailsKey($id),
                    self::TTL_SECONDS,
                    json_encode($data, JSON_THROW_ON_ERROR)
                );
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Возвращает текущее состояние онлайн-посетителей.
     *
     * @return array{count:int,ttl:int,visitors:array<int,array<string,mixed>>}
     */
    public function all(): array
    {
        try {
            $this->cleanup();

            $ids = Redis::zrevrange(self::STORAGE_KEY, 0, -1);

            $visitors = collect($ids)
                ->map(function (string $id) {
                    $raw = Redis::get($this->detailsKey($id));

                    if (! $raw) {
                        return null;
                    }

                    try {
                        /** @var array<string, mixed> $decoded */
                        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    } catch (Throwable) {
                        return null;
                    }

                    return $decoded + [
                        'id' => $id,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return [
                'count' => count($visitors),
                'ttl' => self::TTL_SECONDS,
                'visitors' => $visitors,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'count' => 0,
                'ttl' => self::TTL_SECONDS,
                'visitors' => [],
            ];
        }
    }

    /**
     * Очищает устаревшие записи о посетителях.
     */
    public function cleanup(): void
    {
        try {
            $threshold = Carbon::now()->subSeconds(self::TTL_SECONDS)->getTimestamp();
            $staleIds = Redis::zrangebyscore(self::STORAGE_KEY, '-inf', $threshold);

            if (empty($staleIds)) {
                return;
            }

            Redis::pipeline(function ($pipe) use ($staleIds) {
                $pipe->zrem(self::STORAGE_KEY, ...$staleIds);

                foreach ($staleIds as $id) {
                    $pipe->del($this->detailsKey($id));
                }
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Возвращает ключ для хранения информации по конкретному посетителю.
     */
    private function detailsKey(string $id): string
    {
        return self::DETAILS_PREFIX.$id;
    }
}
