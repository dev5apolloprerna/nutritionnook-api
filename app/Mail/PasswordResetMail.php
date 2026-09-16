<?php
// app/Mail/PasswordResetMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;
    public $userType; // 'customer' or 'chef'

    public function __construct($user, $token, $userType = 'customer')
    {
        $this->user = $user;
        $this->token = $token;
        $this->userType = $userType;
    }

    public function build()
    {
        $resetUrl = $this->userType == 'chef' 
            ? route('chef.password.reset', ['token' => $this->token, 'email' => $this->user->email])
            : route('password.reset', ['token' => $this->token, 'email' => $this->user->email]);

        return $this->subject('Reset Your Password - ' . config('app.name'))
                    ->view('emails.password-reset')
                    ->with([
                        'user' => $this->user,
                        'resetUrl' => $resetUrl,
                        'expiry' => config('auth.passwords.' . ($this->userType == 'chef' ? 'chefs' : 'users') . '.expire')
                    ]);
    }
}