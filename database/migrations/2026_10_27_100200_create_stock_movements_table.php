<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id('stockMovementID');
            $table->unsignedBigInteger('productID');
            $table->unsignedBigInteger('userID');
            $table->string('skuType', 50);
            $table->integer('skuQuantity');
            $table->text('skuNote')->nullable();
            $table->timestamps();

            $table->foreign('productID')
                ->references('productID')
                ->on('products')
                ->restrictOnDelete();
            $table->foreign('userID')
                ->references('userID')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
