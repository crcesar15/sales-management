<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'sent', 'paid', 'held', 'cancelled'])->default('draft');
            $table->enum('discount_type', ['flat', 'percentage'])->default('flat');
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->uuid('token')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index('user_id');
            $table->index('cash_register_shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
