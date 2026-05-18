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
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('vendor_id')->constrained();
            $table->enum('status', ['draft', 'awaiting_approval', 'approved', 'sent', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->date('order_date');
            $table->date('expected_arrival_date')->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('proof_of_payment_type', ['bank_transfer', 'cash', 'check', 'credit_card'])->nullable();
            $table->string('proof_of_payment_number')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('order_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
