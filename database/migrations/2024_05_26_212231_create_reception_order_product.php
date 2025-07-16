<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reception_order_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_order_id')->constrained();
            $table->foreignId('product_variant_id')->constrained();
            $table->float('quantity', 8, 2);
            $table->float('price', 8, 2);
            $table->float('total', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reception_order_product');
    }
};
