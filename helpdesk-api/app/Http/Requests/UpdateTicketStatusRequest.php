<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize()
    {
        // Only admin or disposisi can update status
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'disposisi']);
    }

    public function rules()
    {
        return [
            'status' => 'required|string|in:open,in_progress,resolved,closed',
            'comment' => 'nullable|string',
        ];
    }
}
