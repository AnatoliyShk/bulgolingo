<?php

namespace App\Support\LoadTest;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Record of exactly what one load-test run created, kept as a JSON file on the
 * local disk rather than in a table.
 *
 * Two reasons it is not a table: the target of these runs is a live hosted
 * database, and adding a schema object to it just to track throwaway data is a
 * worse trade than a local file; and teardown needs to be exact, so it deletes
 * recorded id ranges instead of pattern-matching names. The name prefix and the
 * .invalid email domain are still applied to every generated row as a fallback,
 * so a lost manifest leaves the data identifiable rather than orphaned.
 */
final class RunManifest
{
    public const DIRECTORY = 'loadtest';

    public const NAME_PREFIX = '[loadtest]';

    public const EMAIL_DOMAIN = 'loadtest.invalid';

    /**
     * @param  array<string, array{start: int, count: int}>  $blocks
     */
    private function __construct(
        public readonly string $id,
        public readonly string $tier,
        public readonly string $createdAt,
        public array $blocks = [],
        public array $counts = [],
    ) {}

    public static function start(string $tier): self
    {
        return new self(
            id: Str::lower(Str::random(8)),
            tier: $tier,
            createdAt: now()->toIso8601String(),
        );
    }

    public static function load(string $id): ?self
    {
        $path = self::DIRECTORY.'/'.$id.'.json';

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $data = json_decode(Storage::disk('local')->get($path), true);

        return new self(
            id: $data['id'],
            tier: $data['tier'],
            createdAt: $data['created_at'],
            blocks: $data['blocks'] ?? [],
            counts: $data['counts'] ?? [],
        );
    }

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        $runs = [];

        foreach (Storage::disk('local')->files(self::DIRECTORY) as $file) {
            if (! str_ends_with($file, '.json')) {
                continue;
            }

            $run = self::load(basename($file, '.json'));

            if ($run) {
                $runs[] = $run;
            }
        }

        return $runs;
    }

    /**
     * Records the contiguous primary-key block reserved for one table. Teardown
     * deletes by this range, which keeps it off the unindexed foreign-key
     * columns that a cascade would otherwise have to scan.
     */
    public function recordBlock(string $table, int $start, int $count): void
    {
        $this->blocks[$table] = ['start' => $start, 'count' => $count];
    }

    public function recordCount(string $table, int $count): void
    {
        $this->counts[$table] = ($this->counts[$table] ?? 0) + $count;
    }

    /**
     * @return array{start: int, end: int}|null
     */
    public function range(string $table): ?array
    {
        if (! isset($this->blocks[$table]) || $this->blocks[$table]['count'] < 1) {
            return null;
        }

        return [
            'start' => $this->blocks[$table]['start'],
            'end' => $this->blocks[$table]['start'] + $this->blocks[$table]['count'] - 1,
        ];
    }

    public function save(): void
    {
        Storage::disk('local')->put(
            self::DIRECTORY.'/'.$this->id.'.json',
            json_encode([
                'id' => $this->id,
                'tier' => $this->tier,
                'created_at' => $this->createdAt,
                'blocks' => $this->blocks,
                'counts' => $this->counts,
            ], JSON_PRETTY_PRINT)
        );
    }

    public function forget(): void
    {
        Storage::disk('local')->delete(self::DIRECTORY.'/'.$this->id.'.json');
    }
}
