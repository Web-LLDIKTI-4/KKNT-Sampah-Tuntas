<?php

namespace Tests\Feature;

use App\Support\UnbufferedCursor;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PDO;
use Pdo\Mysql;
use RuntimeException;
use Tests\TestCase;

class UnbufferedCursorTest extends TestCase
{
    private function attribute(): int
    {
        return PHP_VERSION_ID >= 80500 ? Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
    }

    private function query(): Builder
    {
        return DB::query()->fromRaw('(SELECT 3 AS n UNION ALL SELECT 1 UNION ALL SELECT 2) AS t')->orderBy('n')->select('n');
    }

    private function assertRestored(int $timeout): void
    {
        $this->assertTrue((bool) DB::getReadPdo()->getAttribute($this->attribute()));
        // Connection accepts queries again and the session value is back
        $this->assertSame($timeout, (int) DB::scalar('SELECT @@SESSION.net_write_timeout'));
    }

    public function test_yields_all_rows_in_order_unbuffered_then_restores_buffered_mode(): void
    {
        $timeout = (int) DB::scalar('SELECT @@SESSION.net_write_timeout');
        $rows = [];
        foreach (UnbufferedCursor::each($this->query()) as $row) {
            $this->assertFalse((bool) DB::getReadPdo()->getAttribute($this->attribute()));
            $rows[] = (int) $row->n;
        }

        $this->assertSame([1, 2, 3], $rows);
        $this->assertRestored($timeout);
    }

    public function test_restores_buffered_mode_when_consumer_fails_mid_iteration(): void
    {
        $timeout = (int) DB::scalar('SELECT @@SESSION.net_write_timeout');

        try {
            foreach (UnbufferedCursor::each($this->query()) as $row) {
                throw new RuntimeException('writer failed');
            }
            $this->fail('Expected the consumer to throw');
        } catch (RuntimeException $e) {
            $this->assertSame('writer failed', $e->getMessage());
        }

        $this->assertRestored($timeout);
    }
}
