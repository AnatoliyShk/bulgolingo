<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The values of App\Enums\MessengerName, copied rather than read from the
     * enum so this migration keeps meaning what it meant when the enum grows.
     */
    private const array NAMES = ['telegram', 'whatsapp', 'viber'];

    /**
     * Run the migrations.
     *
     * messenger_name was free text, so "Telegram" and "telegram " counted as
     * different messengers. Every name is trimmed and lowercased onto the
     * enum's values; accounts that then coincide collapse onto their oldest
     * row, as in add_unique_index_to_messengers. A name outside the list stops
     * the migration and names the offenders, rather than guessing which
     * messenger was meant or deleting the link. The CHECK is Postgres only,
     * like the clause checks; elsewhere the model's enum cast guards it.
     */
    public function up(): void
    {
        $unknown = DB::table('messengers')
            ->whereNotIn(DB::raw('lower(trim(messenger_name))'), self::NAMES)
            ->distinct()
            ->pluck('messenger_name');

        if ($unknown->isNotEmpty()) {
            throw new RuntimeException('Unknown messenger names, map them to one of '.implode(', ', self::NAMES).' first: '.$unknown->implode(', '));
        }

        $duplicates = DB::table('messengers')
            ->select(DB::raw('lower(trim(messenger_name)) as name'), 'messenger_user_id')
            ->groupBy(DB::raw('lower(trim(messenger_name))'), 'messenger_user_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $account) {
            $ids = DB::table('messengers')
                ->where(DB::raw('lower(trim(messenger_name))'), $account->name)
                ->where('messenger_user_id', $account->messenger_user_id)
                ->orderBy('id')
                ->pluck('id');

            DB::table('messengers')->whereIn('id', $ids->slice(1))->delete();
        }

        DB::table('messengers')->update(['messenger_name' => DB::raw('lower(trim(messenger_name))')]);

        if (DB::getDriverName() === 'pgsql') {
            $names = implode(', ', array_map(fn (string $name) => "'{$name}'", self::NAMES));

            DB::statement("ALTER TABLE messengers ADD CONSTRAINT messengers_messenger_name_check CHECK (messenger_name IN ({$names}))");
        }
    }

    /**
     * Reverse the migrations.
     *
     * Names stay lowercase; the original spellings are not recoverable.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE messengers DROP CONSTRAINT IF EXISTS messengers_messenger_name_check');
        }
    }
};
