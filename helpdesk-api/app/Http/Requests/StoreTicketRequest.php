<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize()
    {
        // All authenticated users can create tickets
        return auth()->check();
    }

    public function rules()
    {
        return [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
            'prodi' => 'required|string|max:100',
            'semester' => 'required|string|max:10',
            'no_hp' => 'required|string|max:20',
            'anonymous' => 'boolean',
            'lampiran' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'prioritas' => 'nullable|string|in:low,medium,high,urgent',
        ];
    }
}
