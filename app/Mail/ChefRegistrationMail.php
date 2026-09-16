<?php
// app/Mail/ChefRegistrationMail.php

namespace App\Mail;

use App\Models\Chef;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChefRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $chef;
    public $password;

    public function __construct(Chef $chef, $password = null)
    {
        $this->chef = $chef;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name') . ' - Chef Registration Successful')
                    ->view('emails.chef-registration')
                    ->with([
                        'chef' => $this->chef,
                        'password' => $this->password,
                        'loginUrl' => route('chef.login')
                    ]);
    }
}