<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FAQController extends Controller
{
    // GET /faqs (public)
    public function index()
    {
        $faqs = Faq::with('category')->get();
        return response()->json($faqs);
    }

    // GET /faqs/{id} (public)
    public function show($id)
    {
        $faq = Faq::with('category')->findOrFail($id);
        return response()->json($faq);
    }

    // POST /faqs (admin only)
    public function store(Request $request)
    {
        // Otorisasi sudah dihandle oleh middleware route
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);
        $faq = Faq::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);
        return response()->json($faq, 201);
    }

    // PATCH /faqs/{id} (admin only)
    public function update(Request $request, $id)
    {
        // Otorisasi sudah dihandle oleh middleware route
        $faq = Faq::findOrFail($id);
        $validated = $request->validate([
            'question' => 'sometimes|required|string',
            'answer' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
        ]);
        $faq->update($validated);
        return response()->json($faq);
    }

    // DELETE /faqs/{id} (admin only)
    public function destroy($id)
    {
        // Otorisasi sudah dihandle oleh middleware route
        $faq = Faq::findOrFail($id);
        $faq->delete();
        return response()->json(['message' => 'FAQ deleted']);
    }

    // GET /faqs/categories (public)
    public function categories()
    {
        $categories = Category::has('faqs')->get();
        if ($categories->isEmpty()) {
            return response()->json([]);
        }
        return response()->json($categories);
    }

    // POST /tickets/{id}/convert-to-faq (admin only)
    public function convertFromTicket(Request $request, $ticketId)
    {
        // Otorisasi sudah dihandle oleh middleware route
        $ticket = Ticket::findOrFail($ticketId);
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);
        $faq = Faq::create([
            ...$validated,
            'created_by' => Auth::id(),
            'ticket_id' => $ticket->id,
        ]);
        return response()->json($faq, 201);
    }
}
