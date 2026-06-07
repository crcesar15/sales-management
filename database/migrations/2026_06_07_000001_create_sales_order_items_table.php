<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_unit_id')->nullable()->constrained('product_variant_units')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('conversion_factor')->default(1);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('sales_order_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
