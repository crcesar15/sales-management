<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('vendor_id')->constrained();
            $table->enum('status', ['draft', 'pending', 'paid', 'cancelled'])->default('draft');
            $table->date('order_date')->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->float('sub_total', 8, 2)->nullable();
            $table->float('discount', 8, 2)->nullable();
            $table->float('total', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('proof_of_payment_type')->nullable();
            $table->string('proof_of_payment_number')->nullable();
            $table->timestamps();
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
