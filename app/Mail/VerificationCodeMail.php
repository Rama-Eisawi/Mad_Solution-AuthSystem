<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $verificationCode;
    public function __construct($verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verification Code Mail',
        );
    }
    public function build()
    {
        $this->from('hello@example.com', 'Me')
            ->to('ohzrm5@admin.com', 'Your mail');
        $this->subject('Your Verification Code')
            ->html('<p>Your verification code is: ' . $this->verificationCode . '</p>');

        //return $this->subject('Your Verification Code')
        //   ->html('<p>Your verification code is: ' . $this->verificationCode . '</p>');

    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verificationCode',

        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
