<?php

use App\Enums\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Same shape as the roles table: reference data every user row points at,
     * so it is inserted here rather than by a seeder. The next migration
     * backfills users.type_id from it, and a fresh or test database has these
     * rows without a seed run.
     */
    public function up(): void
    {
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('types')->insert(array_map(
            fn (UserType $name) => ['name' => $name->value, 'created_at' => $now, 'updated_at' => $now],
            UserType::cases()
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('types');
    }
};
