<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('to_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['requested', 'picked', 'in_transit', 'received', 'completed', 'cancelled'])->default('requested');
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['from_store_id', 'status']);
            $table->index(['to_store_id', 'status']);
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
