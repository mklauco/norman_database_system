<?php

namespace App\Mail\Hazards;

use App\Models\Hazards\ApiRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApiFetchEnd extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ApiRun $run)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hazards API Fetch Finished',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hazards.apiFetchEnd',
            with: ['run' => $this->run]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
