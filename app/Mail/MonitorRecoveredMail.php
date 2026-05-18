<?php

namespace App\Mail;

use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonitorRecoveredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Monitor $monitor)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Monitor recovered: {$this->monitor->url}",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.monitors.recovered',
        );
    }
}
