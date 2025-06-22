<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize()
    {
        // All authenticated users can update their tickets
        return auth()->check();
    }

    public function rules()
    {
        return [
            'judul' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'sub_category_id' => 'sometimes|required|integer|exists:sub_categories,id',
            'prodi' => 'sometimes|required|string|max:100',
            'semester' => 'sometimes|required|string|max:10',
            'no_hp' => 'sometimes|required|string|max:20',
            'prioritas' => 'sometimes|required|string|in:low,medium,high,urgent',
        ];
    }
}
