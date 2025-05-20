<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected Transaction $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->transaction->invoice;
        
        return (new MailMessage)
            ->subject('Pembayaran Anda Ditolak')
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Kami ingin memberitahukan bahwa pembayaran Anda tidak dapat diverifikasi atau telah ditolak.')
            ->line('Detail Pembayaran:')
            ->line('- Kode Transaksi: ' . $this->transaction->transaction_code)
            ->line('- Nomor Invoice: ' . ($invoice ? $invoice->invoice_number : 'N/A'))
            ->line('- Jumlah: Rp ' . number_format($this->transaction->amount, 0, ',', '.'))
            ->line('- Metode Pembayaran: ' . $this->transaction->getPaymentMethodTextAttribute())
            ->line('- Tanggal Pembayaran: ' . $this->transaction->payment_date->format('d F Y H:i'))
            ->line('Silakan hubungi tim keuangan kami untuk informasi lebih lanjut atau coba lakukan pembayaran kembali.')
            ->action('Lihat Detail Transaksi', url('/portal/transactions/' . $this->transaction->id))
            ->line('Terima kasih atas pengertian Anda.')
            ->salutation('Salam hormat,\nTim Keuangan');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $invoice = $this->transaction->invoice;
        
        return [
            'title' => 'Pembayaran Ditolak',
            'message' => 'Pembayaran Anda dengan kode ' . $this->transaction->transaction_code . ' telah ditolak.',
            'transaction_id' => $this->transaction->id,
            'invoice_id' => $invoice ? $invoice->id : null,
            'amount' => $this->transaction->amount,
        ];
    }
}
