<?php

namespace App\Mail\Hazards;

use App\Models\Hazards\ParseRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParseComptoxEnd extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ParseRun $run)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hazards COMPTox Parse Finished',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hazards.parseComptoxEnd',
            with: ['run' => $this->run]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

