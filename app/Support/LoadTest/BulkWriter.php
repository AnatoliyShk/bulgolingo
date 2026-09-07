<?php

namespace App\Support\LoadTest;

use Generator;
use Illuminate\Database\Connection;

/**
 * Streams generated rows into Postgres with COPY FROM STDIN.
 *
 * Multi-row INSERT is not viable here: the target is a hosted database reached
 * over the public internet, so per-statement round trips dominate, and the
 * larger tiers write tens of millions of rows. COPY moves a whole chunk in one
 * round trip and skips the SQL parser entirely, which is what makes those tiers
 * finish at all. Any connection that is not pdo_pgsql falls back to chunked
 * INSERT so the same generator still works against a local SQLite database.
 */
final class BulkWriter
{
    public function __construct(private Connection $connection) {}

    /**
     * Writes every row of $rows into $table, flushing once per $chunk rows, and
     * returns the number written. Values are positional and must line up with
     * $columns; null becomes SQL NULL rather than an empty string.
     *
     * @param  array<int, string>  $columns
     * @param  iterable<int, array<int, mixed>>  $rows
     * @param  null|callable(int): void  $onFlush  receives the running total after each chunk
     */
    public function write(string $table, array $columns, iterable $rows, int $chunk = 5_000, ?callable $onFlush = null): int
    {
        $pdo = $this->connection->getPdo();
        $canCopy = $this->connection->getDriverName() === 'pgsql' && method_exists($pdo, 'pgsqlCopyFromArray');

        $written = 0;
        $buffer = [];

        foreach ($rows as $row) {
            $buffer[] = $canCopy ? $this->encodeLine($row) : $row;

            if (count($buffer) < $chunk) {
                continue;
            }

            $this->flush($table, $columns, $buffer, $canCopy);
            $written += count($buffer);
            $buffer = [];

            if ($onFlush) {
                $onFlush($written);
            }
        }

        if ($buffer !== []) {
            $this->flush($table, $columns, $buffer, $canCopy);
            $written += count($buffer);

            if ($onFlush) {
                $onFlush($written);
            }
        }

        return $written;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, mixed>  $buffer
     */
    private function flush(string $table, array $columns, array $buffer, bool $canCopy): void
    {
        if ($canCopy) {
            $this->connection->getPdo()->pgsqlCopyFromArray(
                $table,
                $buffer,
                "\t",
                '\\N',
                implode(',', array_map(fn (string $c) => '"'.$c.'"', $columns)),
            );

            return;
        }

        $this->connection->table($table)->insert(
            array_map(fn (array $row) => array_combine($columns, $row), $buffer)
        );
    }

    /**
     * Encodes one row as a COPY text-format line. The four characters COPY
     * treats structurally — backslash, tab, newline, carriage return — are
     * escaped, and null is emitted as the \N marker rather than an empty field,
     * which COPY would otherwise read as an empty string.
     *
     * @param  array<int, mixed>  $row
     */
    private function encodeLine(array $row): string
    {
        $fields = [];

        foreach ($row as $value) {
            if ($value === null) {
                $fields[] = '\\N';

                continue;
            }

            if (is_bool($value)) {
                $fields[] = $value ? 't' : 'f';

                continue;
            }

            $fields[] = strtr((string) $value, [
                '\\' => '\\\\',
                "\t" => '\\t',
                "\n" => '\\n',
                "\r" => '\\r',
            ]);
        }

        return implode("\t", $fields);
    }

    /**
     * Reserves a contiguous block of $count primary keys from $table's identity
     * sequence and returns the first one. Taking the block up front lets the
     * generator emit explicit ids — which it needs, because child rows reference
     * parents that have not been written yet — without racing anything else that
     * happens to be inserting into the same table.
     */
    public function reserveIds(string $table, int $count): int
    {
        $sequence = $this->connection->selectOne(
            'select pg_get_serial_sequence(?, ?) as name',
            [$table, 'id']
        )->name;

        $start = (int) $this->connection->selectOne('select nextval(?) as v', [$sequence])->v;

        if ($count > 1) {
            $this->connection->selectOne('select setval(?, ?) as v', [$sequence, $start + $count - 1]);
        }

        return $start;
    }

    /**
     * Current high-water mark of $table's identity sequence, or 0 when nothing
     * has consumed it yet. Sampling this either side of a COPY bounds the block
     * of ids that COPY just assigned, which is how a run records what it created
     * for tables whose ids it lets Postgres allocate. It is only meaningful
     * because a load run is the sole writer while it is in flight.
     */
    public function sequenceValue(string $table): int
    {
        $sequence = $this->connection->selectOne(
            'select pg_get_serial_sequence(?, ?) as name',
            [$table, 'id']
        )->name;

        $row = $this->connection->selectOne('select last_value, is_called from '.$sequence);

        return $row->is_called ? (int) $row->last_value : 0;
    }
}
