<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique(['stockKeepUnit']);
            });

            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('categoryID')->nullable()->change();
                $table->unsignedBigInteger('companyID')->nullable()->after('productID');
                $table->decimal('purchasePrice', 15, 2)->default(0)->after('minStockLevel');
                $table->decimal('sellingPrice', 15, 2)->default(0)->after('purchasePrice');

                $table->foreign('companyID')
                    ->references('companyID')
                    ->on('companies')
                    ->restrictOnDelete();
                $table->unique(['companyID', 'stockKeepUnit']);
            });
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['companyID']);
                $table->dropUnique(['companyID', 'stockKeepUnit']);
                $table->dropColumn(['companyID', 'purchasePrice', 'sellingPrice']);
            });

            Schema::table('products', function (Blueprint $table) {
                $table->unique('stockKeepUnit');
            });
        });
    }
};
