<?php

declare(strict_types=1);

namespace Maatify\EventLogging\Tests\Unit;

use DateTimeZone;
use Maatify\SharedCommon\Infrastructure\SystemClock;
use PHPUnit\Framework\TestCase;

final class InfrastructureSmokeTest extends TestCase
{
    public function testAutoloadingAndBasicInstantiation(): void
    {
        $clock = new SystemClock(new DateTimeZone('UTC'));

        $this->assertInstanceOf(\DateTimeImmutable::class, $clock->now());
    }
}
