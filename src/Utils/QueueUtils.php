<?php

namespace Muvinime\Utils;

class QueueUtils
{
    const QUEUE_OPTION_KEY = 'muvinime_queue';

    public static function getQueue(): array
    {
        $queue = get_option(self::QUEUE_OPTION_KEY, []);
        return \is_array($queue) ? $queue : [];
    }

    public static function addQueue(string $data): void
    {
        $decoded = json_decode($data, true);
        if (!\is_array($decoded)) return;

        $oldQueue = self::getQueue();
        $merged = !empty($oldQueue) ? array_merge($oldQueue, $decoded) : $decoded;
        update_option(self::QUEUE_OPTION_KEY, $merged);
    }

    public static function saveQueue(array $data): void
    {
        update_option(self::QUEUE_OPTION_KEY, $data);
    }

    public static function countQueue(): int
    {
        return \count(self::getQueue());
    }
}
