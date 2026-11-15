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
        Schema::create('replacement_cost_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('catalog_id')->nullable()->constrained('catalog')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('purchase_order_item_id')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 30);
            $table->string('supplier_name')->nullable();
            $table->string('unit_name')->nullable();
            $table->decimal('unit_price', 20, 8);
            $table->unsignedInteger('conversion_factor')->default(1);
            $table->decimal('cost', 20, 12);
            $table->decimal('previous_cost', 20, 12)->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['product_variant_id', 'invalidated_at', 'id'], 'replacement_cost_variant_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replacement_cost_events');
    }
};
