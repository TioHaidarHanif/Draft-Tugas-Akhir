<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FAQ;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FAQController extends Controller
{
    /**
     * Display a listing of FAQs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = FAQ::query()
            ->with('category')
            ->where('is_public', true);
        
        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Search by keyword if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => 'success',
            'message' => 'FAQs retrieved successfully',
            'data' => $faqs
        ]);
    }

    /**
     * Display the specified FAQ.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $faq = FAQ::with('category')->find($id);
        
        if (!$faq) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ not found',
                'code' => 404
            ], 404);
        }

        // Increment view count only for public FAQs
        if ($faq->is_public) {
            $faq->incrementViewCount();
        } else {
            // Only allow admin to view non-public FAQs
            if (!Auth::check() || Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ not found',
                    'code' => 404
                ], 404);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ retrieved successfully',
            'data' => $faq
        ]);
    }
     /**
     * Display a listing of the FAQ (for admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function indexAdmin(Request $request): JsonResponse
    {
        // Query tanpa 'where('is_public', true)' karena admin bisa melihat semua FAQ
        $query = FAQ::query()
            ->with('category');
            // ->where('is_public', true); // <--- Hapus baris ini

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by keyword if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => 'success',
            'message' => 'FAQs retrieved successfully',
            'data' => $faqs
        ]);
    }

    /**
     * Display the specified FAQ (for admin only).
     *
     * @param string $id
     * @return JsonResponse
     */
    public function showAdmin(string $id): JsonResponse
    {
        $faq = FAQ::with('category')->find($id);

        if (!$faq) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ not found',
                'code' => 404
            ], 404);
        }

        // Increment view count hanya jika FAQ publik (logika ini tetap bisa ada, opsional)
        // Admin bisa melihat FAQ non-publik, tapi view count hanya untuk yang publik diakses.
        if ($faq->is_public) {
            // Asumsi metode incrementViewCount() ada di model FAQ Anda
            $faq->incrementViewCount();
        }

        // Tidak perlu lagi pemeriksaan Auth::check() atau role di sini,
        // karena middleware 'auth:sanctum' dan 'admin' sudah menjamin user adalah admin dan login.

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ retrieved successfully',
            'data' => $faq
        ]);
    }

    /**
     * Store a newly created FAQ.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }

        $faq = FAQ::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category_id' => $request->category_id,
            'user_id' => Auth::id(),
            'is_public' => $request->is_public ?? true,
            'view_count' => 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ created successfully',
            'data' => $faq
        ], 201);
    }

    /**
     * Update the specified FAQ.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $faq = FAQ::find($id);
        
        if (!$faq) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ not found',
                'code' => 404
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'question' => 'string|max:255',
            'answer' => 'string',
            'category_id' => 'exists:categories,id',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }

        $faq->update($request->only([
            'question',
            'answer',
            'category_id',
            'is_public'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ updated successfully',
            'data' => $faq
        ]);
    }

    /**
     * Remove the specified FAQ.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $faq = FAQ::find($id);
        
        if (!$faq) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ not found',
                'code' => 404
            ], 404);
        }

        $faq->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ deleted successfully'
        ]);
    }

    /**
     * Convert a ticket to FAQ.
     *
     * @param Request $request
     * @param string $ticketId
     * @return JsonResponse
     */
    public function convertTicketToFAQ(Request $request, string $ticketId): JsonResponse
    {
        $ticket = Ticket::find($ticketId);
        
        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found',
                'code' => 404
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }

        $faq = FAQ::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category_id' => $ticket->category_id,
            'user_id' => Auth::id(),
            'ticket_id' => $ticket->id,
            'is_public' => $request->is_public ?? true,
            'view_count' => 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket converted to FAQ successfully',
            'data' => $faq
        ], 201);
    }

    /**
     * Get all FAQ categories.
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = Category::withCount(['faqs' => function($query) {
            $query->where('is_public', true);
        }])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ categories retrieved successfully',
            'data' => $categories
        ]);
    }
}
