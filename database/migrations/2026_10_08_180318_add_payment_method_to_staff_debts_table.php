<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_debts', function (Blueprint $table): void {
            $table->string('payment_method', 30)->nullable();
            $table->index('issued_on');
        });
        Schema::table('staff_repayments', function (Blueprint $table): void {
            $table->index('paid_on');
        });
    }

    public function down(): void
    {
        Schema::table('staff_repayments', function (Blueprint $table): void {
            $table->dropIndex(['paid_on']);
        });
        Schema::table('staff_debts', function (Blueprint $table): void {
            $table->dropIndex(['issued_on']);
            $table->dropColumn('payment_method');
        });
    }
};
