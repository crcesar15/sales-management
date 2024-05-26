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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('reception_order_id')->constrained()->onDelete('cascade');
            $table->date('expiry_date')->nullable();
            $table->integer('initial_quantity');
            $table->integer('remaining_quantity');
            $table->integer('missing_quantity')->default(0);
            $table->integer('sold_quantity')->default(0);
            $table->integer('transferred_quantity')->default(0);
            $table->enum('status', ['queued', 'active', 'closed'])->default('queued');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
