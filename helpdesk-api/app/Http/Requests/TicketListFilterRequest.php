<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketListFilterRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'status' => 'nullable|string|in:new,in_progress,resolved,closed',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sub_category_id' => 'nullable|integer|exists:sub_categories,id',
            'search' => 'nullable|string',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
            'sortBy' => 'nullable|string|in:created_at,status,category_id',
            'sortOrder' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer',
        ];
    }
}
