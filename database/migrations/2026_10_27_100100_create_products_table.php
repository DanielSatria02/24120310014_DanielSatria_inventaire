<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('productID');
            $table->unsignedBigInteger('categoryID');
            $table->string('stockKeepUnit')->unique();
            $table->string('name');
            $table->unsignedInteger('stockQuantity')->default(0);
            $table->unsignedInteger('minStockLevel')->default(0);
            $table->timestamps();

            $table->foreign('categoryID')
                ->references('categoryID')
                ->on('categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
