<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $transaction->transaction_code }}</title>
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
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .receipt-details, .company-details, .customer-details {
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
        .completed {
            background-color: #4CAF50;
        }
        .pending {
            background-color: #ff9800;
        }
        .failed {
            background-color: #f44336;
        }
        .refunded {
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
        .receipt-notes {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 3px solid #ddd;
        }
        .stamp {
            margin-top: 30px;
            text-align: center;
        }
        .stamp-text {
            display: inline-block;
            border: 2px solid #4CAF50;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            color: #4CAF50;
            transform: rotate(-5deg);
            font-size: 24px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            margin-left: auto;
            margin-bottom: 10px;
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
                <div class="receipt-title">BUKTI PEMBAYARAN</div>
                <div><strong>No. Transaksi:</strong> {{ $transaction->transaction_code }}</div>
                <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($transaction->payment_date)->format('d/m/Y H:i') }}</div>
                <div><strong>Status:</strong> 
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
                            <span>{{ $transaction->status }}</span>
                    @endswitch
                </div>
            </div>
        </div>
        
        <div class="customer-details clearfix">
            <div class="col-6">
                <h3>Pembayaran Dari:</h3>
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
            
            @if($invoice)
            <div class="col-6 text-right">
                <h3>Detail Invoice:</h3>
                <div><strong>No. Invoice:</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>Tanggal Invoice:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</div>
                <div><strong>Jatuh Tempo:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</div>
                <div><strong>Status Invoice:</strong> 
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
            @endif
        </div>
        
        <h3>Detail Pembayaran</h3>
        <table>
            <thead>
                <tr>
                    <th width="30%">Metode Pembayaran</th>
                    <th width="30%">Nomor Referensi</th>
                    <th width="40%">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
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
                    <td>{{ $transaction->reference_number ?? '-' }}</td>
                    <td class="text-right amount">Rp {{ number_format(abs($transaction->amount), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="payment-status {{ $transaction->status }}">
            @switch($transaction->status)
                @case('completed')
                    PEMBAYARAN BERHASIL
                    @break
                @case('pending')
                    MENUNGGU PEMBAYARAN
                    @break
                @case('failed')
                    PEMBAYARAN GAGAL
                    @break
                @case('refunded')
                    DANA DIKEMBALIKAN
                    @break
                @default
                    {{ strtoupper($transaction->status) }}
            @endswitch
        </div>
        
        @if($transaction->notes)
        <div class="receipt-notes">
            <strong>Catatan:</strong><br>
            {{ $transaction->notes }}
        </div>
        @endif
        
        @if($transaction->status === 'completed')
        <div class="stamp">
            <div class="stamp-text">LUNAS</div>
        </div>
        @endif
        
        <div class="signature">
            <div class="signature-line"></div>
            <div>{{ $company['name'] }}</div>
        </div>
        
        <div class="footer">
            <p>Terima kasih atas pembayaran Anda.</p>
            <p>{{ $company['name'] }} &copy; {{ date('Y') }}</p>
            <p>{{ $company['website'] }}</p>
        </div>
    </div>
</body>
</html>
