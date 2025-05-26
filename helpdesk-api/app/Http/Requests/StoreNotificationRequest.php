<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize()
    {
        // Only authenticated users can create notification manually
        return auth()->check();
    }

    public function rules()
    {
        return [
            'recipient_id' => 'nullable|exists:users,id',
            'recipientRole' => 'nullable|string|in:student,admin,disposisi',
            'ticket_id' => 'nullable|exists:tickets,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:new_ticket,assignment,status_change,feedback,custom',
        ];
    }
}
