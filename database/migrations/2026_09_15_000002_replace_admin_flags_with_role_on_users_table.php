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
     * The column is added nullable, backfilled from the two flags and only
     * then made required, since existing rows have no role to take a default
     * from. A user carrying both flags becomes a full admin, the wider of the
     * two grants.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained();
        });

        $roles = DB::table('roles')->pluck('id', 'name');

        DB::table('users')->update(['role_id' => $roles[RoleName::Student->value]]);
        DB::table('users')->where('is_admin_visitor', true)->update(['role_id' => $roles[RoleName::AdminVisitor->value]]);
        DB::table('users')->where('is_admin', true)->update(['role_id' => $roles[RoleName::Admin->value]]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
            $table->dropColumn(['is_admin', 'is_admin_visitor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->boolean('is_admin_visitor')->default(false)->after('is_admin');
        });

        $roles = DB::table('roles')->pluck('id', 'name');

        DB::table('users')->where('role_id', $roles[RoleName::Admin->value])->update(['is_admin' => true]);
        DB::table('users')->where('role_id', $roles[RoleName::AdminVisitor->value])->update(['is_admin_visitor' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
