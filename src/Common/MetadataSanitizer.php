<?php

declare(strict_types=1);

namespace Maatify\EventLogging\Common;

final class MetadataSanitizer
{
    /**
     * @template TKey of array-key
     * @param array<TKey, mixed> $metadata
     * @param list<string> $sensitiveKeys
     * @return array<TKey, mixed>
     */
    public static function sanitize(array $metadata, array $sensitiveKeys = ['password', 'token', 'secret', 'key', 'authorization', 'cookie']): array
    {
        foreach ($metadata as $key => $value) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos((string) $key, $sensitiveKey) !== false) {
                    $metadata[$key] = '[redacted]';
                    continue 2;
                }
            }

            if (is_array($value)) {
                $metadata[$key] = self::sanitize($value, $sensitiveKeys);
            }
        }

        return $metadata;
    }
}
