<?php

// app/Http/Controllers/EmailTestController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;
use Exception;

class EmailTestController extends Controller
{
    public function sendTestEmail()
    {
        $toEmail = 'martin@klauco.com'; // Replace with the recipient's email address
        $messageContent = 'This is a test email.';

        try {
            Mail::to($toEmail)->send(new TestEmail($messageContent));
            return response()->json(['status' => 'Email sent successfully']);
        } catch (Exception $e) {
            // Log the error message for debugging
            \Log::error('Email sending failed: ' . $e->getMessage());

            // Return the error message as a JSON response
            return response()->json(['status' => 'Email sending failed', 'error' => $e->getMessage()]);
        }
    }
}

