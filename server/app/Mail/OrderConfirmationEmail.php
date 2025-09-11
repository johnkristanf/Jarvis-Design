<?php

namespace App\Mail;

use App\Models\Orders;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Orders $orders)
    {
        //
    }

   
    public function envelope(): Envelope
    {
        // The "from" details is automatically set by the mail config file
        return new Envelope(
            subject: 'Order Confirmation Email',
        );
    }

    public function content(): Content
    {
        // Public constructor data will be automatically accessible in the view content
        return new Content(
            view: 'emails.order-confirmation',
        );
    }


    public function attachments(): array
    {
        return [];
    }
}
