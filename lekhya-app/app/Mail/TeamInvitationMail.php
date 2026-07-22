<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $inviteeName,
        public string $companyName,
        public string $inviterName,
        public string $roleLabel,
        public string $acceptUrl,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->inviterName} invited you to {$this->companyName} on " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.team-invitation');
    }
}
