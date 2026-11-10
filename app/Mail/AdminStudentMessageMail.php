<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminStudentMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $messageTitle;
    public string $messageBody;
    public string $recipientName;

    public function __construct(string $messageTitle, string $messageBody, string $recipientName)
    {
        $this->messageTitle = $messageTitle;
        $this->messageBody  = $messageBody;
        $this->recipientName = $recipientName;
    }

    public function build()
    {
        return $this->subject($this->messageTitle)
            ->view('emails.admin-student-message');
    }
}
