<?php

declare(strict_types=1);

namespace Maatify\EventLogging\Tests\Unit\Common;

use Maatify\EventLogging\Common\MetadataSanitizer;
use PHPUnit\Framework\TestCase;

class MetadataSanitizerTest extends TestCase
{
    public function testSanitizesSensitiveKeys(): void
    {
        $metadata = [
            'public_info' => 'visible',
            'password' => 'secret123',
            'api_token' => 'abc',
            'secret_key' => 'xyz',
            'authorization' => 'Bearer token',
            'session_cookie' => 'val'
        ];

        $sanitized = MetadataSanitizer::sanitize($metadata);

        $this->assertSame('visible', $sanitized['public_info']);
        $this->assertSame('[redacted]', $sanitized['password']);
        $this->assertSame('[redacted]', $sanitized['api_token']);
        $this->assertSame('[redacted]', $sanitized['secret_key']);
        $this->assertSame('[redacted]', $sanitized['authorization']);
        $this->assertSame('[redacted]', $sanitized['session_cookie']);
    }

    public function testSanitizesNestedArrays(): void
    {
        $metadata = [
            'user' => [
                'name' => 'John',
                'password' => 'secret123'
            ]
        ];

        $sanitized = MetadataSanitizer::sanitize($metadata);

        $user = $sanitized['user'];
        if (!is_array($user)) {
            self::fail('Expected sanitized user metadata to remain an array.');
        }

        $this->assertSame('John', $user['name'] ?? null);
        $this->assertSame('[redacted]', $user['password'] ?? null);
    }

    public function testPreservesNumericKeysAndShapeWithoutMutatingOriginal(): void
    {
        $metadata = [
            0 => ['visible' => 'first', 'password' => 'secret-one'],
            1 => ['visible' => 'second', 'nested' => ['token' => 'secret-two']],
            'items' => ['one', 'two'],
        ];

        $sanitized = MetadataSanitizer::sanitize($metadata);

        $this->assertSame([
            0 => ['visible' => 'first', 'password' => '[redacted]'],
            1 => ['visible' => 'second', 'nested' => ['token' => '[redacted]']],
            'items' => ['one', 'two'],
        ], $sanitized);
        $this->assertSame([0, 1, 'items'], array_keys($sanitized));
        $this->assertSame('secret-one', $metadata[0]['password']);
        $this->assertSame('secret-two', $metadata[1]['nested']['token']);
    }

    public function testCustomSensitiveKeys(): void
    {
        $metadata = [
            'password' => 'secret123',
            'custom_secret' => 'hide_me'
        ];

        $sanitized = MetadataSanitizer::sanitize($metadata, ['custom_secret']);

        // 'password' is not in the custom list, so it shouldn't be redacted
        $this->assertSame('secret123', $sanitized['password']);
        $this->assertSame('[redacted]', $sanitized['custom_secret']);
    }
}
