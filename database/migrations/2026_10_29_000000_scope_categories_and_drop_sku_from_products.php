<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('companyID')->nullable()->after('categoryID');

                $table->foreign('companyID')
                    ->references('companyID')
                    ->on('companies')
                    ->cascadeOnDelete();
                $table->unique(['companyID', 'name']);
            });

            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique(['companyID', 'stockKeepUnit']);
            });

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('stockKeepUnit');
            });
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('products', function (Blueprint $table) {
                $table->string('stockKeepUnit')->nullable()->after('companyID');
                $table->unique(['companyID', 'stockKeepUnit']);
            });

            Schema::table('categories', function (Blueprint $table) {
                $table->dropForeign(['companyID']);
                $table->dropUnique(['companyID', 'name']);
                $table->dropColumn('companyID');
            });
        });
    }
};
