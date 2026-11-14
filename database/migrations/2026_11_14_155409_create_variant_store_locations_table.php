<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_store_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('location_code', 50);
            $table->timestamps();
            $table->unique(['product_variant_id', 'store_id']);
            $table->index(['store_id', 'location_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_store_locations');
    }
};
