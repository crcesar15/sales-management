<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receivable_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['charge', 'payment']);
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index('sales_order_id');
            $table->index('sales_order_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receivable_entries');
    }
};
