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
        Schema::create('reception_order_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reception_order_id')->constrained();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_product')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained();
            $table->decimal('quantity', 10, 4);
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->date('expiry_date')->nullable();
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
