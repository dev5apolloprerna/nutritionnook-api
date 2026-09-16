<?php
// app/Mail/ChefPayoutMail.php

namespace App\Mail;

use App\Models\Chef;
use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChefPayoutMail extends Mailable
{
    use Queueable, SerializesModels;

    public $chef;
    public $payout;
    public $status;

    public function __construct(Chef $chef, Payout $payout, $status = 'processed')
    {
        $this->chef = $chef;
        $this->payout = $payout;
        $this->status = $status;
    }

    public function build()
    {
        $subject = $this->status == 'processed' 
            ? 'Payout Processed Successfully' 
            : 'Payout Failed - Action Required';

        return $this->subject($subject . ' - ' . config('app.name'))
                    ->view('emails.chef-payout')
                    ->with([
                        'chef' => $this->chef,
                        'payout' => $this->payout,
                        'status' => $this->status
                    ]);
    }
}