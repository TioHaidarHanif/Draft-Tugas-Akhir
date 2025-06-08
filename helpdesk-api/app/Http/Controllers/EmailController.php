<?php

namespace App\Http\Controllers;

use App\Mail\ManualEmail;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * Send a manual email to the specified address
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Send the email
            Mail::to($request->email)
                ->send(new ManualEmail($request->subject, $request->body));

            // Log the email
            $emailLog = EmailLog::create([
                'user_id' => auth()->id(),
                'to_email' => $request->email,
                'subject' => $request->subject,
                'content' => $request->body,
                'status' => 'sent',
                'error_message' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Email sent successfully',
                'data' => $emailLog
            ], 200);

        } catch (\Exception $e) {
            // Log the error
            $emailLog = EmailLog::create([
                'user_id' => auth()->id(),
                'to_email' => $request->email,
                'subject' => $request->subject,
                'content' => $request->body,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email logs (admin only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logs(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $logs = EmailLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Email logs retrieved successfully',
            'data' => $logs
        ], 200);
    }
}
