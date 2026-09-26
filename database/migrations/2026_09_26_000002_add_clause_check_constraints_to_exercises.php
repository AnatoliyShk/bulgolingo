<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One CHECK per exercise type, each guarding only the clause keys the rest
     * of the schema or the player depends on, mirroring the required keys of
     * ExerciseType::dataRules(). Keys are required, never forbidden, so a clause
     * can grow new keys without touching these. A type with no entry here is
     * not constrained at all.
     */
    private const array RULES = [
        'multiple_choice' => <<<'SQL'
            jsonb_typeof(clause->'explanation') = 'string'
            AND jsonb_path_exists(clause, 'strict $.pairs ? (@.type() == "array" && @.size() >= 5)', '{}', true)
            AND NOT jsonb_path_exists(clause, 'strict $.pairs[*] ? (@.type() != "array" || @.size() != 2)', '{}', true)
            AND NOT jsonb_path_exists(clause, 'strict $.pairs[*][*] ? (@.type() != "string")', '{}', true)
            AND (clause->'order' IS NULL OR jsonb_typeof(clause->'order') = 'object')
            SQL,
        'true_false' => <<<'SQL'
            jsonb_typeof(clause->'sentence') = 'string'
            AND jsonb_typeof(clause->'correct_option') = 'boolean'
            AND jsonb_typeof(clause->'explanation') = 'string'
            SQL,
        'fill_in_the_blank' => <<<'SQL'
            jsonb_typeof(clause->'sentence') = 'string'
            AND jsonb_typeof(clause->'explanation') = 'string'
            AND jsonb_path_exists(clause, 'strict $ ? (@.options.type() == "array" && @.options.size() >= 1
                && @.correct_option.type() == "number" && @.correct_option == @.correct_option.floor()
                && @.correct_option >= 0 && @.correct_option < @.options.size())', '{}', true)
            SQL,
        'image_matching' => <<<'SQL'
            jsonb_typeof(clause->'explanation') = 'string'
            AND jsonb_path_exists(clause, 'strict $ ? (@.options.type() == "array" && @.options.size() >= 2
                && @.correct_option.type() == "number" && @.correct_option == @.correct_option.floor()
                && @.correct_option >= 0 && @.correct_option < @.options.size())', '{}', true)
            AND NOT jsonb_path_exists(clause, 'strict $.options[*] ? (@.type() != "string")', '{}', true)
            SQL,
    ];

    /**
     * Run the migrations.
     *
     * Postgres only: SQLite has no jsonb or jsonpath, and the local SQLite
     * setup relies on the model-level validation alone.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::RULES as $type => $rule) {
            DB::statement("ALTER TABLE exercises ADD CONSTRAINT exercises_clause_{$type}_check CHECK (decision_type <> '{$type}' OR coalesce(({$rule}), false))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::RULES) as $type) {
            DB::statement("ALTER TABLE exercises DROP CONSTRAINT IF EXISTS exercises_clause_{$type}_check");
        }
    }
};
