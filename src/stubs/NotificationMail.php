<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use WebRegulate\LaravelAdministration\Classes\NotificationBase;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param \WebRegulate\LaravelAdministration\Classes\NotificationBase $notificationDefinition
     * @param array $toAddresses ['email@domain.com', ...]
     * @param string $passedSubject
     * @param ?array<int, \Illuminate\Mail\Mailables\Attachment> $passedAttachments
     */
    public function __construct(
        public NotificationBase $notificationDefinition,
        public array $toAddresses,
        public string $passedSubject,
        public ?array $passedAttachments = null,
    ) {
        $this->notificationDefinition->setDisplayMode('email');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')),
            to: $this->toAddresses,
            subject: $this->passedSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'email.notification-mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return $this->passedAttachments ?? [];
    }
}
