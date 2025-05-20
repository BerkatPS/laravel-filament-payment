<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .invoice-details, .company-details, .customer-details {
            margin-bottom: 20px;
        }
        .row {
            display: flex;
            margin-bottom: 10px;
        }
        .col-6 {
            width: 50%;
            float: left;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .amount {
            font-weight: bold;
        }
        .payment-status {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            padding: 10px;
            text-align: center;
            color: white;
        }
        .paid {
            background-color: #4CAF50;
        }
        .unpaid {
            background-color: #f44336;
        }
        .pending {
            background-color: #ff9800;
        }
        .cancelled {
            background-color: #9e9e9e;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .invoice-notes {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 3px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header clearfix">
            <div class="col-6">
                <div class="logo">{{ $company['name'] }}</div>
                <div>{{ $company['address'] }}</div>
                <div>{{ $company['city'] }}</div>
                <div>Tel: {{ $company['phone'] }}</div>
                <div>Email: {{ $company['email'] }}</div>
                <div>NPWP: {{ $company['tax_id'] }}</div>
            </div>
            <div class="col-6 text-right">
                <div class="invoice-title">INVOICE</div>
                <div><strong>No. Invoice:</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</div>
                <div><strong>Jatuh Tempo:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</div>
                <div><strong>Status:</strong> 
                    @switch($invoice->status)
                        @case('paid')
                            <span style="color: #4CAF50;">Dibayar</span>
                            @break
                        @case('overdue')
                            <span style="color: #f44336;">Terlambat</span>
                            @break
                        @case('draft')
                            <span style="color: #9e9e9e;">Draft</span>
                            @break
                        @case('sent')
                            <span style="color: #ff9800;">Terkirim</span>
                            @break
                        @case('cancelled')
                            <span style="color: #9e9e9e;">Dibatalkan</span>
                            @break
                        @default
                            <span>{{ $invoice->status }}</span>
                    @endswitch
                </div>
            </div>
        </div>
        
        <div class="customer-details clearfix">
            <div class="col-6">
                <h3>Tagihan Kepada:</h3>
                <div><strong>{{ $customer->name }}</strong></div>
                @if($customer->company_name)
                    <div>{{ $customer->company_name }}</div>
                @endif
                @if($customer->address)
                    <div>{{ $customer->address }}</div>
                @endif
                @if($customer->phone)
                    <div>Tel: {{ $customer->phone }}</div>
                @endif
                <div>Email: {{ $customer->email }}</div>
                @if($customer->tax_id)
                    <div>NPWP: {{ $customer->tax_id }}</div>
                @endif
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="45%">Deskripsi</th>
                    <th width="15%">Jumlah</th>
                    <th width="15%">Harga</th>
                    <th width="20%">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>{{ $invoice->description ?? 'Pembayaran Layanan' }}</td>
                    <td>1</td>
                    <td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->tax_amount > 0)
                <tr>
                    <td colspan="4" class="text-right">Subtotal</td>
                    <td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right">Pajak</td>
                    <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($invoice->discount_id && $invoice->discount_amount > 0)
                <tr>
                    <td colspan="4" class="text-right">Diskon ({{ $invoice->discount->name }})</td>
                    <td class="text-right">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="4" class="text-right"><strong>Total</strong></td>
                    <td class="text-right amount">Rp {{ number_format($invoice->final_amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        
        @if($invoice->transactions && $invoice->transactions->count() > 0)
        <h3>Riwayat Pembayaran</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Transaksi</th>
                    <th>Metode</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->transactions as $transaction)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($transaction->payment_date)->format('d/m/Y') }}</td>
                    <td>{{ $transaction->transaction_code }}</td>
                    <td>
                        @switch($transaction->payment_method)
                            @case('cash')
                                Tunai
                                @break
                            @case('bank_transfer')
                                Transfer Bank
                                @break
                            @case('credit_card')
                                Kartu Kredit
                                @break
                            @case('debit_card')
                                Kartu Debit
                                @break
                            @case('ewallet')
                                E-Wallet
                                @break
                            @case('qris')
                                QRIS
                                @break
                            @default
                                {{ $transaction->payment_method }}
                        @endswitch
                    </td>
                    <td class="text-right">Rp {{ number_format(abs($transaction->amount), 0, ',', '.') }}</td>
                    <td>
                        @switch($transaction->status)
                            @case('completed')
                                <span style="color: #4CAF50;">Selesai</span>
                                @break
                            @case('pending')
                                <span style="color: #ff9800;">Tertunda</span>
                                @break
                            @case('failed')
                                <span style="color: #f44336;">Gagal</span>
                                @break
                            @case('refunded')
                                <span style="color: #9e9e9e;">Dikembalikan</span>
                                @break
                            @default
                                {{ $transaction->status }}
                        @endswitch
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        
        <div>
            <div class="payment-status {{ $invoice->status == 'paid' ? 'paid' : ($invoice->status == 'overdue' ? 'unpaid' : ($invoice->status == 'cancelled' ? 'cancelled' : 'pending')) }}">
                @switch($invoice->status)
                    @case('paid')
                        LUNAS
                        @break
                    @case('overdue')
                        TERLAMBAT
                        @break
                    @case('draft')
                        DRAFT
                        @break
                    @case('sent')
                        BELUM LUNAS
                        @break
                    @case('cancelled')
                        DIBATALKAN
                        @break
                    @default
                        {{ strtoupper($invoice->status) }}
                @endswitch
            </div>
        </div>
        
        @if($invoice->notes)
        <div class="invoice-notes">
            <strong>Catatan:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif
        
        <div class="footer">
            <p>Terima kasih atas pembayaran Anda.</p>
            <p>{{ $company['name'] }} &copy; {{ date('Y') }}</p>
            <p>{{ $company['website'] }}</p>
        </div>
    </div>
</body>
</html>
