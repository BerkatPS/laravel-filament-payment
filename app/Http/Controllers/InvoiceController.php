<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Print invoice as PDF
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function print(Invoice $invoice)
    {
        $data = [
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'transactions' => $invoice->transactions,
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

        $pdf = PDF::loadView('pdfs.invoice', $data);
        
        return $pdf->stream("Invoice-{$invoice->invoice_number}.pdf");
    }
}
