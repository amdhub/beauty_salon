<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_debts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('employee_name_snapshot');
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('repaid_minor')->default(0);
            $table->string('currency', 10);
            $table->date('issued_on');
            $table->date('due_on')->nullable()->index();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('actor_name_snapshot');
            $table->uuid('idempotency_key');
            $table->timestamps();
            $table->unique(['created_by', 'idempotency_key']);
            $table->index(['employee_id', 'id']);
        });
        Schema::create('staff_repayments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_debt_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->date('paid_on');
            $table->string('payment_method', 30);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('actor_name_snapshot');
            $table->uuid('idempotency_key');
            $table->timestamps();
            $table->unique(['created_by', 'idempotency_key']);
            $table->index(['staff_debt_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_repayments');
        Schema::dropIfExists('staff_debts');
    }
};
