<?php

namespace App\Support;

use Generator;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Builder;
use PDO;
use Pdo\Mysql;

/**
 * Streams a query's rows in one pass with constant memory. lazy() re-runs the query per chunk (OFFSET paging),
 * a buffered cursor() holds the whole result in PHP memory. While iterating, no other query may run on this
 * connection (MySQL rejects it until the unbuffered result is freed).
 */
class UnbufferedCursor
{
    // Seconds MySQL waits on a slow reader (PHP writes xlsx between fetches) before dropping the connection
    private const NET_WRITE_TIMEOUT = 600;

    public static function each(Builder $query): Generator
    {
        $connection = $query->getConnection();
        if (! $connection instanceof MySqlConnection) {
            yield from $query->cursor();

            return;
        }

        // Same PDO that cursor() selects for this builder
        $pdo = $query->useWritePdo ? $connection->getPdo() : $connection->getReadPdo();
        $attribute = PHP_VERSION_ID >= 80500 ? Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
        $buffered = $pdo->getAttribute($attribute);
        // Session timeout read/set on that same PDO (a read replica has its own session)
        $timeout = (int) $pdo->query('SELECT @@SESSION.net_write_timeout')->fetchColumn();

        $rows = null;

        try {
            $pdo->exec('SET SESSION net_write_timeout = '.max($timeout, self::NET_WRITE_TIMEOUT));
            $pdo->setAttribute($attribute, false);
            $rows = $query->cursor()->getIterator();
            foreach ($rows as $row) {
                yield $row;
            }
        } finally {
            // Free the unbuffered statement first, otherwise the connection rejects the queries below
            unset($rows);
            $pdo->setAttribute($attribute, $buffered);
            $pdo->exec('SET SESSION net_write_timeout = '.$timeout);
        }
    }
}
