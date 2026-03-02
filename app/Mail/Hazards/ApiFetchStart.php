<?php

namespace App\Mail\Hazards;

use App\Models\Hazards\ApiRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApiFetchStart extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ApiRun $run)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hazards API Fetch Started',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hazards.apiFetchStart',
            with: ['run' => $this->run]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
