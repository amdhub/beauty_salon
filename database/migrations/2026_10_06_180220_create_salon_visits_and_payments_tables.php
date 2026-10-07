<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_visits', function (Blueprint $table): void {
            $table->id();
            $table->uuid('checkout_key')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name_snapshot');
            $table->string('customer_phone_snapshot', 25)->nullable();
            $table->string('salon_name_snapshot');
            $table->string('cashier_name_snapshot');
            $table->string('currency', 10);
            $table->string('source')->default('walk_in');
            $table->string('status')->default('open');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('created_at');
        });
        Schema::create('salon_visit_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('salon_visit_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('service_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->string('category_name_snapshot');
            $table->string('employee_name_snapshot')->nullable();
            $table->decimal('price_snapshot', 12, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('salon_visit_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('reference')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('cashier_name_snapshot');
            $table->timestamp('payment_date')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('salon_visit_items');
        Schema::dropIfExists('salon_visits');
    }
};
