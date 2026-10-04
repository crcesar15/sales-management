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
            $table->unsignedInteger('conversion_factor')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->unique(['reception_order_id', 'purchase_order_item_id'], 'reception_order_po_item_unique');
        });

        Schema::create('reception_order_batch_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('reception_order_product_id');
            $table->foreign('reception_order_product_id', 'reception_batch_allocation_product_fk')
                ->references('id')
                ->on('reception_order_product')
                ->cascadeOnDelete();
            $table->unsignedInteger('purchase_unit_quantity')->default(0);
            $table->unsignedInteger('loose_unit_quantity')->default(0);
            $table->unsignedInteger('base_quantity');
            $table->date('expiry_date')->nullable();
            $table->string('batch_identifier')->nullable();
            $table->timestamps();

            $table->index('reception_order_product_id', 'reception_batch_allocation_product_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reception_order_batch_allocations');
        Schema::dropIfExists('reception_order_product');
    }
};
