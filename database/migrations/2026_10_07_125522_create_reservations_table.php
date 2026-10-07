<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('service_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->decimal('price_snapshot', 12, 2);
            $table->string('currency', 10);
            $table->unsignedInteger('duration_minutes');
            $table->string('customer_name', 150);
            $table->string('phone', 25);
            $table->string('email')->nullable();
            $table->dateTime('preferred_at');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('processed_by_name')->nullable();
            $table->timestamps();
            $table->index(['status', 'preferred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
