<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the column first, then the constraint in its own ALTER statement.
        // MySQL cannot add a column and a foreign key on that column reliably in
        // a single blueprint call, so keep them separated.
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('companyID')->nullable()->after('categoryID');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('companyID')
                ->references('companyID')
                ->on('companies')
                ->cascadeOnDelete();
            $table->unique(['companyID', 'name']);
        });

        // The unique index on (companyID, stockKeepUnit) is currently the index
        // MySQL uses to enforce products_companyid_foreign. It refuses to drop
        // an index that backs a foreign key (error 1553), so drop the foreign key
        // first and re-create it after the column/index are gone.
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['companyID']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['companyID', 'stockKeepUnit']);
            $table->dropColumn('stockKeepUnit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('companyID')
                ->references('companyID')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['companyID']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('stockKeepUnit')->nullable()->after('companyID');
            $table->unique(['companyID', 'stockKeepUnit']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('companyID')
                ->references('companyID')
                ->on('companies')
                ->restrictOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['companyID']);
            $table->dropUnique(['companyID', 'name']);
            $table->dropColumn('companyID');
        });
    }
};