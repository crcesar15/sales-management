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
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('status', ['draft', 'sent', 'paid', 'canceled'])->default('draft');
            $table->enum('payment_method', ['cash', 'credit_card', 'qr', 'stripe', 'transfer'])->default('cash');
            $table->text('notes')->nullable();
            $table->float('sub_total', 10, 2);
            $table->float('discount', 10, 2)->default(0);
            $table->float('total', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_orders');
    }
};
