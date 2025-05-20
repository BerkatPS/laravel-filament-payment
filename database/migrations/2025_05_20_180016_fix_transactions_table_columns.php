<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'transaction_code')) {
                $table->string('transaction_code')->unique()->after('id');
            }

            if (!Schema::hasColumn('transactions', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('transactions', 'is_manual')) {
                $table->boolean('is_manual')->default(false)->after('status');
            }

            if (!Schema::hasColumn('transactions', 'notes')) {
                $table->text('notes')->nullable()->after('is_manual');
            }

            // Jika kolom refunded_transaction_id belum ada, tambahkan
            if (!Schema::hasColumn('transactions', 'refunded_transaction_id')) {
                $table->unsignedBigInteger('refunded_transaction_id')->nullable()->after('notes');
                $table->foreign('refunded_transaction_id')->references('id')->on('transactions')->nullOnDelete();
            }
        });

        // Solusi 2: Buat transaction_number berdasarkan transaction_code jika kosong
        DB::statement("UPDATE transactions SET transaction_number = transaction_code WHERE transaction_number IS NULL OR transaction_number = ''");

        // Solusi 3: Buat kolom transaction_number bisa NULL (opsional)
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transaction_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
