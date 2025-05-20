<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transaction_code')->nullable()->after('id');
            $table->string('reference_number')->nullable()->after('payment_method');
            $table->string('payment_proof')->nullable()->after('reference_number');
            $table->text('notes')->nullable()->after('status');
            $table->boolean('is_manual')->default(false)->after('notes');
            $table->foreignId('refunded_transaction_id')->nullable()->after('is_manual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['transaction_code', 'reference_number', 'payment_proof', 'notes', 'is_manual', 'refunded_transaction_id']);
        });
    }
};
