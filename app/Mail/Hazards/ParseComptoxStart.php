<?php

namespace App\Mail\Hazards;

use App\Models\Hazards\ParseRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParseComptoxStart extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ParseRun $run)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hazards COMPTox Parse Started',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hazards.parseComptoxStart',
            with: ['run' => $this->run]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

