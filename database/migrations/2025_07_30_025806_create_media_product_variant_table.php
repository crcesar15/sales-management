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
        Schema::create('media_product_variant', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['media_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_product_variant');
    }
};
