<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $services = Service::all();
        $discounts = Discount::all();
        
        // Menggunakan awalan unik untuk mencegah duplikasi
        $prefix = 'INV-' . date('Ymd') . '-' . Str::random(3);

        // Jika ada customer John Doe
        $johnDoe = $customers->where('email', 'john@example.com')->first();
        if ($johnDoe) {
            // Invoice pertama - sudah lunas
            $invoice1 = $this->createInvoiceIfNotExists(
                $prefix . '-001',
                [
                    'customer_id' => $johnDoe->id,
                    'issue_date' => Carbon::now()->subDays(30),
                    'due_date' => Carbon::now()->subDays(15),
                    'amount' => 2000000,
                    'tax_amount' => 220000,
                    'discount_id' => $discounts->random()->id,
                    'discount_amount' => 200000,
                    'final_amount' => 2020000,
                    'notes' => 'Pembayaran untuk layanan desain website',
                    'status' => 'paid',
                ]
            );

            // Tambahkan service ke invoice
            if ($invoice1) {
                $webDesign = $services->where('name', 'like', '%Website Design%')->first();
                if ($webDesign) {
                    $invoice1->services()->attach($webDesign->id, [
                        'quantity' => 1,
                        'price' => 2000000,
                        'total' => 2000000,
                    ]);
                }
            }
        }

        // Invoice untuk Ahmad
        $ahmad = $customers->where('email', 'ahmad@example.com')->first();
        if ($ahmad) {
            // Invoice - pending
            $invoice2 = $this->createInvoiceIfNotExists(
                $prefix . '-002',
                [
                    'customer_id' => $ahmad->id,
                    'issue_date' => Carbon::now()->subDays(10),
                    'due_date' => Carbon::now()->addDays(5),
                    'amount' => 1500000,
                    'tax_amount' => 165000,
                    'discount_id' => null,
                    'discount_amount' => 0,
                    'final_amount' => 1665000,
                    'notes' => 'Pembayaran untuk jasa SEO',
                    'status' => 'sent',
                ]
            );

            // Tambahkan service ke invoice
            if ($invoice2) {
                $seo = $services->where('name', 'like', '%SEO%')->first();
                if ($seo) {
                    $invoice2->services()->attach($seo->id, [
                        'quantity' => 1,
                        'price' => 1500000,
                        'total' => 1500000,
                    ]);
                }
            }
        }

        // Invoice untuk Jane Smith
        $jane = $customers->where('email', 'jane@example.com')->first();
        if ($jane) {
            // Invoice - overdue
            $invoice3 = $this->createInvoiceIfNotExists(
                $prefix . '-003',
                [
                    'customer_id' => $jane->id,
                    'issue_date' => Carbon::now()->subDays(45),
                    'due_date' => Carbon::now()->subDays(15),
                    'amount' => 3000000,
                    'tax_amount' => 330000,
                    'discount_id' => $discounts->random()->id,
                    'discount_amount' => 300000,
                    'final_amount' => 3030000,
                    'notes' => 'Pembayaran untuk pengembangan aplikasi mobile',
                    'status' => 'overdue',
                ]
            );

            // Tambahkan service ke invoice
            if ($invoice3) {
                $mobileDev = $services->where('name', 'like', '%Mobile App%')->first();
                if ($mobileDev) {
                    $invoice3->services()->attach($mobileDev->id, [
                        'quantity' => 1,
                        'price' => 3000000,
                        'total' => 3000000,
                    ]);
                }
            }
        }

        // Invoice untuk Siti Rahma
        $siti = $customers->where('email', 'siti@example.com')->first();
        if ($siti) {
            // Invoice - paid
            $invoice4 = $this->createInvoiceIfNotExists(
                $prefix . '-005',
                [
                    'customer_id' => $siti->id,
                    'issue_date' => Carbon::now()->subDays(20),
                    'due_date' => Carbon::now()->subDays(5),
                    'amount' => 4000000,
                    'tax_amount' => 440000,
                    'discount_id' => $discounts->random()->id,
                    'discount_amount' => 400000,
                    'final_amount' => 4040000,
                    'notes' => 'Pembayaran untuk pembuatan content marketing',
                    'status' => 'paid',
                ]
            );

            // Tambahkan service ke invoice
            if ($invoice4) {
                $contentMarketing = $services->where('name', 'like', '%Content%')->first();
                if ($contentMarketing) {
                    $invoice4->services()->attach($contentMarketing->id, [
                        'quantity' => 2,
                        'price' => 2000000,
                        'total' => 4000000,
                    ]);
                }
            }
        }

        // Invoice untuk Budi Santoso
        $budi = $customers->where('email', 'budi@example.com')->first();
        if ($budi) {
            // Invoice - cancelled
            $invoice5 = $this->createInvoiceIfNotExists(
                $prefix . '-006',
                [
                    'customer_id' => $budi->id,
                    'issue_date' => Carbon::now()->subDays(60),
                    'due_date' => Carbon::now()->subDays(30),
                    'amount' => 2500000,
                    'tax_amount' => 275000,
                    'discount_id' => null,
                    'discount_amount' => 0,
                    'final_amount' => 2775000,
                    'notes' => 'Pembayaran untuk jasa maintenance (dibatalkan)',
                    'status' => 'cancelled',
                ]
            );

            // Tambahkan service ke invoice
            if ($invoice5) {
                $maintenance = $services->where('name', 'like', '%Maintenance%')->first();
                if ($maintenance) {
                    $invoice5->services()->attach($maintenance->id, [
                        'quantity' => 5,
                        'price' => 500000,
                        'total' => 2500000,
                    ]);
                }
            }
        }

        // Invoice untuk Demo User
        $customer = $customers->where('email', 'customer@example.com')->first();
        if ($customer) {
            // Invoice - pending
            $invoice6 = $this->createInvoiceIfNotExists(
                $prefix . '-007',
                [
                    'customer_id' => $customer->id,
                    'issue_date' => Carbon::now()->subDays(5),
                    'due_date' => Carbon::now()->addDays(10),
                    'amount' => 1800000,
                    'tax_amount' => 198000,
                    'discount_id' => $discounts->random()->id,
                    'discount_amount' => 180000,
                    'final_amount' => 1818000,
                    'notes' => 'Pembayaran untuk jasa konsultasi digital marketing',
                    'status' => 'sent',
                ]
            );

            // Tambahkan service ke invoice
            if ($invoice6) {
                $digitalMarketing = $services->where('name', 'like', '%Digital Marketing%')->first();
                if ($digitalMarketing) {
                    $invoice6->services()->attach($digitalMarketing->id, [
                        'quantity' => 3,
                        'price' => 600000,
                        'total' => 1800000,
                    ]);
                }
            }
        }
    }
    
    /**
     * Membuat invoice hanya jika belum ada dengan nomor invoice yang sama
     */
    private function createInvoiceIfNotExists(string $invoiceNumber, array $data): ?Invoice
    {
        // Cek apakah invoice dengan nomor ini sudah ada
        if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            return null;
        }
        
        // Jika tidak ada, buat invoice baru
        return Invoice::create(array_merge(['invoice_number' => $invoiceNumber], $data));
    }
}
