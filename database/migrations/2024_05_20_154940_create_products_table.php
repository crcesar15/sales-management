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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->constrained();
            $table->foreignId('measure_unit_id')->constrained();
            $table->string('identifier');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('stock', 8, 2)->nullable()->default(0);
            $table->json('options')->nullable();
            $table->string('correlation_hash')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
