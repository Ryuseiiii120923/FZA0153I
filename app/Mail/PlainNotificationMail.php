<?php
// app/Mail/PlainNotificationMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlainNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
    ) {}

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->text('emails.plain-notification')
            ->with(['body' => $this->bodyText]);
    }
}