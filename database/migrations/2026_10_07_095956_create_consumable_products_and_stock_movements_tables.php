<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('reference', 60)->unique();
            $table->string('unit', 15);
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consumable_product_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', 12, 3);
            $table->decimal('stock_after', 12, 3);
            $table->string('product_name_snapshot', 150);
            $table->string('unit_snapshot', 15);
            $table->date('movement_date');
            $table->string('supplier')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->string('currency', 10);
            $table->foreignId('employee_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('employee_name_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('actor_name_snapshot');
            $table->uuid('idempotency_key');
            $table->timestamps();
            $table->unique(['created_by', 'idempotency_key']);
            $table->index(['consumable_product_id', 'id']);
            $table->index('movement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('consumable_products');
    }
};
