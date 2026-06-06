<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['cash_in', 'cash_out']);
            $table->decimal('amount', 12, 2);
            $table->string('reason', 255);
            $table->timestamps();

            $table->index('cash_register_shift_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_movements');
    }
};
