<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reorder_at')->default(5);
            $table->unsignedInteger('unit_cost_cents')->default(0);
            $table->timestamps();

            $table->index(['quantity', 'reorder_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
