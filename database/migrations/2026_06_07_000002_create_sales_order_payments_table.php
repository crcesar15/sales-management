<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_method', ['cash', 'credit_card', 'qr', 'transfer']);
            $table->decimal('amount', 12, 2);
            $table->decimal('tendered_amount', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('reference', 255)->nullable();
            $table->timestamps();

            $table->index('sales_order_id');
            $table->index('user_id');
            $table->index('cash_register_shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_payments');
    }
};
