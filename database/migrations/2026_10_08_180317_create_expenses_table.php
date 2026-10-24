<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->string('category', 30);
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 10);
            $table->date('paid_on')->index();
            $table->string('payment_method', 30);
            $table->string('payee')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('actor_name_snapshot');
            $table->uuid('idempotency_key');
            $table->timestamps();
            $table->unique(['created_by', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
