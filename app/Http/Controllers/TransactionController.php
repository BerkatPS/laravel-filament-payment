<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    /**
     * Print transaction receipt as PDF
     *
     * @param Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function receipt(Transaction $transaction)
    {
        $data = [
            'transaction' => $transaction,
            'customer' => $transaction->customer,
            'invoice' => $transaction->invoice,
            'company' => [
                'name' => config('app.name'),
                'address' => 'Jl. Contoh Alamat No. 123',
                'city' => 'Jakarta',
                'phone' => '+62 812 3456 7890',
                'email' => 'info@example.com',
                'website' => 'example.com',
                'tax_id' => '12.345.678.9-123.000',
            ],
        ];

        $pdf = PDF::loadView('pdfs.receipt', $data);
        
        return $pdf->stream("Receipt-{$transaction->transaction_code}.pdf");
    }
}
