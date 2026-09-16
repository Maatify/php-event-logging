<?php

declare(strict_types=1);

namespace Maatify\EventLogging\Tests\Unit\Common;

use Maatify\EventLogging\Common\UrlSanitizer;
use PHPUnit\Framework\TestCase;

class UrlSanitizerTest extends TestCase
{
    public function testSanitizesSensitiveQueryString(): void
    {
        $url = 'https://example.com/api?user=john&token=secret123&signature=abc';
        $sanitized = UrlSanitizer::sanitize($url);

        $this->assertStringContainsString('user=john', $sanitized);
        $this->assertStringContainsString('token=%5Bredacted%5D', $sanitized);
        $this->assertStringContainsString('signature=%5Bredacted%5D', $sanitized);
    }

    public function testNoQueryString(): void
    {
        $url = 'https://example.com/api';
        $sanitized = UrlSanitizer::sanitize($url);

        $this->assertSame($url, $sanitized);
    }

    public function testCustomSensitiveKeys(): void
    {
        $url = 'https://example.com/api?token=secret123&custom_secret=hide_me';
        $sanitized = UrlSanitizer::sanitize($url, ['custom_secret']);

        $this->assertStringContainsString('token=secret123', $sanitized);
        $this->assertStringContainsString('custom_secret=%5Bredacted%5D', $sanitized);
    }

    public function testSanitizePathReturnsPathOnlyWithoutQueryOrFragment(): void
    {
        $sanitized = UrlSanitizer::sanitizePath(
            'https://example.com/reset/token/abc123?next=/dashboard#fragment'
        );

        $this->assertSame('/reset/token/[redacted]', $sanitized);
    }

    public function testSanitizePathPreservesPathOnlyInputAndMasksSensitiveValue(): void
    {
        $this->assertSame(
            '/account/password/[redacted]/profile',
            UrlSanitizer::sanitizePath('/account/password/s3cret/profile')
        );
    }

    public function testSanitizePathMasksSensitiveMarkersCaseInsensitively(): void
    {
        $sanitized = UrlSanitizer::sanitizePath('/download/SIGNATURE/abc/Secret/value');

        $this->assertSame('/download/SIGNATURE/[redacted]/Secret/[redacted]', $sanitized);
    }

    public function testSanitizePathDoesNotCorruptNonSensitivePaths(): void
    {
        $this->assertSame('/public/keynote/abc123', UrlSanitizer::sanitizePath('/public/keynote/abc123'));
    }

    public function testSanitizePathFailsSafelyForMalformedInput(): void
    {
        $malformed = 'http://?password=leaked#fragment';
        $sanitized = UrlSanitizer::sanitizePath($malformed);

        $this->assertSame('[redacted]', $sanitized);
        $this->assertStringNotContainsString('leaked', $sanitized);
        $this->assertStringNotContainsString('?', $sanitized);
        $this->assertStringNotContainsString('#', $sanitized);
    }
}
