<?php

namespace App\Http\Controllers;

use App\Mail\ManualMail;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    // POST /emails/send
    public function send(Request $request)
    {
        // Otorisasi admin
        if (!Auth::user() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $log = EmailLog::create([
            'recipient_email' => $validated['email'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'status' => 'pending',
        ]);

        try {
            Mail::to($validated['email'])->send(new ManualMail($validated['subject'], $validated['body']));
            $log->status = 'sent';
            $log->sent_at = now();
            $log->save();
            return response()->json(['message' => 'Email sent successfully']);
        } catch (\Exception $e) {
            $log->status = 'failed';
            $log->error_message = $e->getMessage();
            $log->save();
            return response()->json(['message' => 'Failed to send email', 'error' => $e->getMessage()], 500);
        }
    }

    // GET /emails/logs
    public function logs(Request $request)
    {
        if (!Auth::user() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $logs = EmailLog::orderByDesc('created_at')->paginate(20);
        return response()->json($logs);
    }
}
