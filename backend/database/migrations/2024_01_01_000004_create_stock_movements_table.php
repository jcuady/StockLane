<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('delta');
            $table->unsignedInteger('quantity_after');
            $table->string('reason', 64);
            $table->string('reference', 191)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
