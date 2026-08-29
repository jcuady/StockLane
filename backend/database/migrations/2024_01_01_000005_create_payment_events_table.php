<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 191)->unique();
            $table->string('event_type', 128);
            $table->string('status', 32)->default('received');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount_cents')->nullable();
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
