<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevealTicketTokenRequest extends FormRequest
{
    public function authorize()
    {
        // Otorisasi di controller
        return true;
    }

    public function rules()
    {
        return [
            'password' => ['required', 'string'],
        ];
    }
}
