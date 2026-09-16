<?php
// app/Mail/CustomerRegistrationMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name') . ' - Registration Successful')
                    ->view('emails.customer-registration')
                    ->with([
                        'user' => $this->user,
                        'loginUrl' => route('login')
                    ]);
    }
}