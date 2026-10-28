<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('companyID')->nullable()->after('stockMovementID');
            $table->decimal('unitPrice', 15, 2)->default(0)->after('skuQuantity');
            $table->decimal('totalAmount', 15, 2)->default(0)->after('unitPrice');

            $table->foreign('companyID')
                ->references('companyID')
                ->on('companies')
                ->restrictOnDelete();
            $table->index(['companyID', 'skuType']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['companyID']);
            $table->dropIndex(['companyID', 'skuType']);
            $table->dropColumn(['companyID', 'unitPrice', 'totalAmount']);
        });
    }
};
