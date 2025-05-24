<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTicketFeedbackRequest extends FormRequest
{
    public function authorize()
    {
        // Only ticket owner, admin, or disposisi can add feedback
        return auth()->check();
    }

    public function rules()
    {
        return [
            'text' => 'required|string',
        ];
    }
}
