<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize()
    {
        // Only admin can assign ticket
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules()
    {
        return [
            'assigned_to' => 'required|exists:users,id',
        ];
    }
}
