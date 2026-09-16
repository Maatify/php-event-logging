<?php

declare(strict_types=1);

namespace Maatify\EventLogging\Common;

final class UrlSanitizer
{
    /** @param list<string> $sensitiveKeys */
    public static function sanitize(string $url, array $sensitiveKeys = ['token', 'secret', 'password', 'key', 'signature']): string
    {
        $parts = parse_url($url);
        if (! isset($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $query);
        foreach ($query as $key => $_) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos((string) $key, $sensitiveKey) !== false) {
                    $query[$key] = '[redacted]';
                    break;
                }
            }
        }

        $sanitizedQuery = http_build_query($query);
        $base = strtok($url, '?');

        return $sanitizedQuery === '' ? (string) $base : (string) $base . '?' . $sanitizedQuery;
    }

    /**
     * Return a path-only value with sensitive marker/value pairs redacted.
     *
     * @param list<string> $sensitiveKeys
     */
    public static function sanitizePath(string $url, array $sensitiveKeys = ['token', 'secret', 'password', 'key', 'signature']): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return '[redacted]';
        }

        $path = $parts['path'] ?? '';

        // Keep this boundary safe even if a malformed parser result contains delimiters.
        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];
        $segments = explode('/', $path);

        foreach ($segments as $index => $segment) {
            if (! self::isSensitiveMarker($segment, $sensitiveKeys)) {
                continue;
            }

            $valueIndex = $index + 1;
            if (isset($segments[$valueIndex]) && $segments[$valueIndex] !== '') {
                $segments[$valueIndex] = '[redacted]';
            }
        }

        return implode('/', $segments);
    }

    /** @param list<string> $sensitiveKeys */
    private static function isSensitiveMarker(string $segment, array $sensitiveKeys): bool
    {
        $decodedSegment = rawurldecode($segment);

        foreach ($sensitiveKeys as $sensitiveKey) {
            $marker = trim($sensitiveKey);
            if ($marker === '') {
                continue;
            }

            $pattern = '/(?:^|[-_.])' . preg_quote($marker, '/') . '(?=$|[-_.])/i';
            if (preg_match($pattern, $decodedSegment) === 1) {
                return true;
            }
        }

        return false;
    }
}
