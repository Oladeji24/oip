<?php
// SendGridController.php
// Controller for handling SendGrid (email/OTP) API actions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SendGridConnector;

class SendGridController extends Controller
{
    protected $sendgrid;

    public function __construct(SendGridConnector $sendgrid)
    {
        $this->sendgrid = $sendgrid;
    }

    // Example: Send email
    public function send(Request $request)
    {
        $to = $request->input('to');
        $subject = $request->input('subject');
        $content = $request->input('content');
        $result = $this->sendgrid->sendEmail($to, $subject, $content);
        return response()->json($result);
    }
}
