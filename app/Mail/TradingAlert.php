<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TradingAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $message;
    public $data;

    /**
     * Create a new message instance.
     *
     * @param string $title
     * @param string $message
     * @param array $data
     * @return void
     */
    public function __construct($title, $message, $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->title)
                    ->markdown("emails.trading.alert");
    }
}
