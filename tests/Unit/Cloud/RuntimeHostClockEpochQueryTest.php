<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimeHostClockEpochQueryTest extends TestCase
{
    #[Test]
    public function mysql_epoch_query_does_not_double_interpret_utc_datetime_in_session_timezone(): void
    {
        $query = RuntimeHostClockIdentity::databaseEpochQueryForDriver('mysql');

        self::assertStringContainsString('UNIX_TIMESTAMP(CURRENT_TIMESTAMP(6))', $query);
        self::assertStringNotContainsString('UTC_TIMESTAMP', $query);
    }

    #[Test]
    public function mariadb_uses_the_same_timezone_safe_epoch_query(): void
    {
        self::assertSame(
            RuntimeHostClockIdentity::databaseEpochQueryForDriver('mysql'),
            RuntimeHostClockIdentity::databaseEpochQueryForDriver('mariadb'),
        );
    }
}
