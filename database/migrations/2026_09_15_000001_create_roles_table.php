<?php

use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The roles are reference data every user row points at, so they are
     * inserted here rather than by a seeder: the next migration backfills
     * users.role_id from them, and a fresh or test database has them without
     * a seed run.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert(array_map(
            fn (RoleName $name) => ['name' => $name->value, 'created_at' => $now, 'updated_at' => $now],
            RoleName::cases()
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
