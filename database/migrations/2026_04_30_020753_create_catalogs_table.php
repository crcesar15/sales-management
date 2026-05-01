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
        Schema::create('catalog', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('product_variant_units')->nullOnDelete();
            $table->float('price', 8);
            $table->string('payment_terms', 15)->nullable();
            $table->string('details', 300)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('minimum_order_quantity')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'product_variant_id', 'unit_id']);
            $table->index('status');
            $table->index('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog');
    }
};
