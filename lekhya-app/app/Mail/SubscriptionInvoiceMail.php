<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array $data invoice view-model  @param string $pdf raw PDF bytes */
    public function __construct(public array $data, public string $pdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ' . config('prabhas.name') . ' subscription invoice — ' . $this->data['invoice_number'],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-invoice', with: ['d' => $this->data]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, str_replace('/', '-', $this->data['invoice_number']) . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
