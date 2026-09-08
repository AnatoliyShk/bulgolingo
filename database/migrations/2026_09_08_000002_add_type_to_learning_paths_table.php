<?php

use App\Enums\LearningPathType;
use App\Support\LoadTest\RunManifest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which audience a path is published to. Everything already in the table
     * predates the distinction and is therefore regular, which is also the
     * default for anything created without an explicit type.
     *
     * The one exception is paths a previous load-test run left behind: those
     * are retagged as tests here by their generated name prefix, since the
     * seeder only tags the runs that come after this migration and the point of
     * the type is to keep generated content out of the catalog.
     *
     * The column is indexed because the catalog filters on it for every visitor
     * and a load-test run puts thousands of test rows in the way of that scan.
     */
    public function up(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->string('type')
                ->default(LearningPathType::Regular->value)
                ->after('language')
                ->index();
        });

        DB::table('learning_paths')
            ->where('name', 'like', RunManifest::NAME_PREFIX.'%')
            ->update(['type' => LearningPathType::Test->value]);
    }

    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
