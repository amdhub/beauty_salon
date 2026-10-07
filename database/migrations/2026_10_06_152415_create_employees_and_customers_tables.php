<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name')->index();
            $table->string('phone', 25)->nullable();
            $table->string('email')->nullable();
            $table->string('position');
            $table->date('hire_date')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('employee_specialties', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['employee_id', 'service_category_id']);
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone', 25)->unique();
            $table->string('secondary_phone', 25)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('gender', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->text('allergies')->nullable();
            $table->foreignId('preferred_beautician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->index(['last_name', 'first_name']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('employee_specialties');
        Schema::dropIfExists('employees');
    }
};
