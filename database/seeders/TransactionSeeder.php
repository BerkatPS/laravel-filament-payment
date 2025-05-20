<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::all();

        // Transaksi untuk invoice John Doe yang sudah lunas
        $paidInvoice = $invoices->where('invoice_number', 'like', '%-001')->first();
        if ($paidInvoice) {
            // Pembayaran penuh
            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd') . '-001',
                'transaction_code' => 'TC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'invoice_id' => $paidInvoice->id,
                'customer_id' => $paidInvoice->customer_id,
                'amount' => $paidInvoice->final_amount,
                'payment_method' => 'bank_transfer',
                'payment_date' => Carbon::parse($paidInvoice->issue_date)->addDays(3),
                'status' => 'completed',
                'reference_number' => 'BT123456789',
                'payment_proof' => null, // No proof for older transactions
                'notes' => 'Pembayaran melalui transfer bank',
                'is_manual' => true,
            ]);
        }

        // Transaksi untuk invoice Jane Smith yang overdue
        $overdueInvoice = $invoices->where('invoice_number', 'like', '%-003')->first();
        if ($overdueInvoice) {
            // Pembayaran sebagian
            $partialAmount = $overdueInvoice->final_amount * 0.3; // 30% dari total
            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd') . '-002',
                'transaction_code' => 'TC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'invoice_id' => $overdueInvoice->id,
                'customer_id' => $overdueInvoice->customer_id,
                'amount' => $partialAmount,
                'payment_method' => 'credit_card',
                'payment_date' => Carbon::parse($overdueInvoice->issue_date)->addDays(15),
                'status' => 'completed',
                'reference_number' => 'CC987654321',
                'payment_proof' => null,
                'notes' => 'Pembayaran sebagian melalui kartu kredit',
                'is_manual' => true,
            ]);
        }

        // Transaksi untuk invoice Siti Rahma yang sudah lunas
        $siti_invoice = $invoices->where('invoice_number', 'like', '%-005')->first();
        if ($siti_invoice) {
            // Pembayaran penuh dengan dua transaksi
            $firstAmount = $siti_invoice->final_amount * 0.6; // 60% pembayaran pertama
            $secondAmount = $siti_invoice->final_amount * 0.4; // 40% pembayaran kedua

            // Transaksi pertama
            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd') . '-003',
                'transaction_code' => 'TC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'invoice_id' => $siti_invoice->id,
                'customer_id' => $siti_invoice->customer_id,
                'amount' => $firstAmount,
                'payment_method' => 'bank_transfer',
                'payment_date' => Carbon::parse($siti_invoice->issue_date)->addDays(4),
                'status' => 'completed',
                'reference_number' => 'BT654987321',
                'payment_proof' => null,
                'notes' => 'Pembayaran pertama',
                'is_manual' => true,
            ]);

            // Transaksi kedua
            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd') . '-004',
                'transaction_code' => 'TC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'invoice_id' => $siti_invoice->id,
                'customer_id' => $siti_invoice->customer_id,
                'amount' => $secondAmount,
                'payment_method' => 'e_wallet',
                'payment_date' => Carbon::parse($siti_invoice->issue_date)->addDays(6),
                'status' => 'completed',
                'reference_number' => 'EW102938475',
                'payment_proof' => null,
                'notes' => 'Pelunasan pembayaran melalui e-wallet',
                'is_manual' => true,
            ]);
        }

        // Transaksi gagal untuk invoice Budi Santoso yang cancelled
        $cancelled_invoice = $invoices->where('invoice_number', 'like', '%-006')->first();
        if ($cancelled_invoice) {
            // Transaksi gagal
            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd') . '-005',
                'transaction_code' => 'TC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'invoice_id' => $cancelled_invoice->id,
                'customer_id' => $cancelled_invoice->customer_id,
                'amount' => $cancelled_invoice->final_amount,
                'payment_method' => 'debit_card',
                'payment_date' => Carbon::parse($cancelled_invoice->issue_date)->addDays(5),
                'status' => 'failed',
                'reference_number' => 'DC567891234',
                'payment_proof' => null,
                'notes' => 'Pembayaran gagal karena dana tidak mencukupi',
                'is_manual' => true,
            ]);
        }

        // Transaksi refund
        $paidInvoice = $invoices->where('invoice_number', 'like', '%-001')->first();
        if ($paidInvoice) {
            // Refund sebagian
            $refundAmount = -500000.00; // Nilai negatif untuk pengembalian dana
            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd') . '-006',
                'transaction_code' => 'TC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'invoice_id' => $paidInvoice->id,
                'customer_id' => $paidInvoice->customer_id,
                'amount' => $refundAmount,
                'payment_method' => 'bank_transfer',
                'payment_date' => Carbon::parse($paidInvoice->issue_date)->addDays(20),
                'status' => 'refunded',
                'reference_number' => 'RF324156789',
                'payment_proof' => null,
                'notes' => 'Pengembalian dana sebagian karena perubahan ketentuan layanan',
                'is_manual' => true,
            ]);
        }

        // Transaksi pending untuk invoice baru
        $pendingInvoice = $invoices->where('invoice_number', 'like', '%-007')->first();
        if ($pendingInvoice) {
            // Transaksi pending
            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd') . '-007',
                'transaction_code' => 'TC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'invoice_id' => $pendingInvoice->id,
                'customer_id' => $pendingInvoice->customer_id,
                'amount' => $pendingInvoice->final_amount,
                'payment_method' => 'bank_transfer',
                'payment_date' => now(),
                'status' => 'pending',
                'reference_number' => 'BT789456123',
                'payment_proof' => null,
                'notes' => 'Menunggu verifikasi admin',
                'is_manual' => false,
            ]);
        }
    }
}
