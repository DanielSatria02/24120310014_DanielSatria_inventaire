<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('companyID')->nullable()->after('userID');
            $table->string('role', 20)->default('employee')->after('password');

            $table->foreign('companyID')
                ->references('companyID')
                ->on('companies')
                ->restrictOnDelete();
        });

        // Accounts created before companies existed become managers of their own company.
        DB::table('users')->get()->each(function ($user) {
            $companyID = DB::table('companies')->insertGetId([
                'name' => $user->name."'s Company",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('userID', $user->userID)->update([
                'companyID' => $companyID,
                'role' => 'manager',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['companyID']);
            $table->dropColumn(['companyID', 'role']);
        });
    }
};
