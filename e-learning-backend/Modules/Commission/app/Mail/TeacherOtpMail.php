<?php

namespace Modules\Commission\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TeacherOtpMail extends Mailable
{
    public string $subject;

    public function __construct(
        public string $otp,
        public string $purpose,
        public string $recipientName,
    ) {
        $this->subject = $purpose === 'password_change'
            ? 'Mã xác minh đổi mật khẩu'
            : 'Mã xác minh đổi email';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.teacher-otp');
    }

    public function attachments(): array
    {
        return [];
    }
}
