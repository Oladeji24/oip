<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountSummary extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @param array $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $period = $this->data["period"] ?? "weekly";
        $periodText = ucfirst($period);

        return $this->subject("{$periodText} Trading Summary - OIP Trading Bot")
                    ->markdown("emails.trading.summary");
    }
}
