<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $subjectText
     * @param string $bodyHtml
     * @param array $fileAttachments Array of absolute file paths or Attachment instances
     */
    public function __construct(
        public string $subjectText,
        public string $bodyHtml,
        public array $fileAttachments = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.templated',
            with: [
                'subject' => $this->subjectText,
                'body' => $this->bodyHtml,
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->fileAttachments as $att) {
            if ($att instanceof Attachment) {
                $attachments[] = $att;
            } elseif (is_string($att) && file_exists($att)) {
                $attachments[] = Attachment::fromPath($att);
            }
        }

        return $attachments;
    }
}
