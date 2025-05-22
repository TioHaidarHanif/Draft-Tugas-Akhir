<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketAttachment;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Ticket::with(['category', 'subcategory', 'creator', 'assignedTo']);
        
        // Filter tickets based on user role
        if ($user->role === 'user') {
            // Users can only see their own tickets
            $query->where('creator_id', $user->id);
        } elseif ($user->role === 'disposisi') {
            // Disposisi can see tickets assigned to them or unassigned
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereNull('assigned_to');
            });
        }
        // Admins can see all tickets

        // Apply filters if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ticket_id', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $perPage = $request->input('per_page', 10);
        $tickets = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => [
                'tickets' => $tickets->items(),
                'pagination' => [
                    'total' => $tickets->total(),
                    'per_page' => $tickets->perPage(),
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created ticket in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:sub_categories,id',
            'priority' => 'required|in:low,medium,high',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ]);

        // Create the ticket
        $ticket = Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'priority' => $request->priority,
            'status' => 'open',
            'creator_id' => Auth::id(),
        ]);

        // Store ticket history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status' => 'open',
            'comments' => 'Ticket created',
        ]);

        // Handle attachments if any
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket_attachments', 'public');
                
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Create notification for admins
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'New Ticket Created',
                'content' => "A new ticket #{$ticket->ticket_id} has been created by {$ticket->creator->name}",
                'read' => false,
                'type' => 'ticket_created',
                'data' => json_encode([
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_id,
                ]),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket created successfully',
            'data' => [
                'ticket' => $ticket->load(['category', 'subcategory', 'creator']),
            ]
        ], 201);
    }

    /**
     * Display the specified ticket.
     *
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user has permission to view this ticket
        if ($user->role === 'user' && $ticket->creator_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        if ($user->role === 'disposisi' && $ticket->assigned_to !== $user->id && !is_null($ticket->assigned_to)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Load relationships
        $ticket->load([
            'category', 
            'subcategory', 
            'creator', 
            'assignedTo', 
            'attachments',
            'histories' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'feedback'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'ticket' => $ticket,
            ]
        ]);
    }

    /**
     * Update the specified ticket in storage.
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user has permission to update this ticket
        if ($user->role === 'user' && $ticket->creator_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'subcategory_id' => 'sometimes|required|exists:sub_categories,id',
            'priority' => 'sometimes|required|in:low,medium,high',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ]);

        // Update ticket
        $ticket->update($request->only([
            'title', 'description', 'category_id', 'subcategory_id', 'priority'
        ]));

        // Store ticket history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status' => $ticket->status,
            'comments' => 'Ticket updated',
        ]);

        // Handle attachments if any
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket_attachments', 'public');
                
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Create notifications
        if ($user->id !== $ticket->creator_id) {
            Notification::create([
                'user_id' => $ticket->creator_id,
                'title' => 'Ticket Updated',
                'content' => "Your ticket #{$ticket->ticket_id} has been updated",
                'read' => false,
                'type' => 'ticket_updated',
                'data' => json_encode([
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_id,
                ]),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket updated successfully',
            'data' => [
                'ticket' => $ticket->load(['category', 'subcategory', 'creator', 'assignedTo']),
            ]
        ]);
    }

    /**
     * Remove the specified ticket from storage (soft delete).
     *
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $user = Auth::user();
        
        // Only admins and ticket creators can delete tickets
        if ($user->role !== 'admin' && $ticket->creator_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Soft delete the ticket
        $ticket->delete();

        // Store ticket history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status' => 'deleted',
            'comments' => 'Ticket deleted',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully',
        ]);
    }

    /**
     * Update the status of a ticket.
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $user = Auth::user();
        
        // Check permissions - only admin and disposisi can change status
        if ($user->role === 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed,reopened',
            'comments' => 'nullable|string',
        ]);

        // Update ticket status
        $oldStatus = $ticket->status;
        $ticket->status = $request->status;
        $ticket->save();

        // Create ticket history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status' => $request->status,
            'comments' => $request->comments ?? "Status changed from {$oldStatus} to {$request->status}",
        ]);

        // Create notification for ticket creator
        Notification::create([
            'user_id' => $ticket->creator_id,
            'title' => 'Ticket Status Updated',
            'content' => "Your ticket #{$ticket->ticket_id} status has been changed to {$request->status}",
            'read' => false,
            'type' => 'ticket_status_updated',
            'data' => json_encode([
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
            ]),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket status updated successfully',
            'data' => [
                'ticket' => $ticket->load(['category', 'subcategory', 'creator', 'assignedTo']),
            ]
        ]);
    }

    /**
     * Assign a ticket to a staff member.
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function assignTicket(Request $request, Ticket $ticket): JsonResponse
    {
        $user = Auth::user();
        
        // Only admin can assign tickets
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'comments' => 'nullable|string',
        ]);

        // Verify the user being assigned has 'disposisi' role
        $assignedUser = \App\Models\User::findOrFail($request->assigned_to);
        if ($assignedUser->role !== 'disposisi') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only assign tickets to disposisi staff',
            ], 422);
        }

        // Update ticket
        $oldAssignee = $ticket->assigned_to;
        $ticket->assigned_to = $request->assigned_to;
        
        // If status is 'open', change to 'in_progress' when assigned
        if ($ticket->status === 'open') {
            $ticket->status = 'in_progress';
        }
        
        $ticket->save();

        // Create ticket history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status' => $ticket->status,
            'comments' => $request->comments ?? "Ticket assigned to {$assignedUser->name}",
        ]);

        // Create notification for assigned user
        Notification::create([
            'user_id' => $request->assigned_to,
            'title' => 'Ticket Assigned',
            'content' => "Ticket #{$ticket->ticket_id} has been assigned to you",
            'read' => false,
            'type' => 'ticket_assigned',
            'data' => json_encode([
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_id,
            ]),
        ]);

        // Create notification for ticket creator
        Notification::create([
            'user_id' => $ticket->creator_id,
            'title' => 'Ticket Assigned',
            'content' => "Your ticket #{$ticket->ticket_id} has been assigned to a staff member",
            'read' => false,
            'type' => 'ticket_assigned',
            'data' => json_encode([
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_id,
            ]),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket assigned successfully',
            'data' => [
                'ticket' => $ticket->load(['category', 'subcategory', 'creator', 'assignedTo']),
            ]
        ]);
    }

    /**
     * Add a comment to a ticket.
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function addComment(Request $request, Ticket $ticket): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user has permission to comment on this ticket
        if ($user->role === 'user' && $ticket->creator_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        if ($user->role === 'disposisi' && $ticket->assigned_to !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'comment' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ]);

        // Create ticket history with comment
        $history = TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status' => $ticket->status,
            'comments' => $request->comment,
        ]);

        // Handle attachments if any
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket_attachments', 'public');
                
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'history_id' => $history->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Create notification for involved parties
        $notifyUsers = [$ticket->creator_id];
        
        if ($ticket->assigned_to && $ticket->assigned_to !== Auth::id()) {
            $notifyUsers[] = $ticket->assigned_to;
        }
        
        // Also notify admins if needed
        if ($user->role !== 'admin') {
            $adminIds = \App\Models\User::where('role', 'admin')->pluck('id')->toArray();
            $notifyUsers = array_merge($notifyUsers, $adminIds);
        }
        
        // Remove current user from notification list
        $notifyUsers = array_diff($notifyUsers, [Auth::id()]);
        
        foreach (array_unique($notifyUsers) as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => 'New Comment on Ticket',
                'content' => "New comment added to ticket #{$ticket->ticket_id} by {$user->name}",
                'read' => false,
                'type' => 'ticket_comment',
                'data' => json_encode([
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_id,
                    'comment_id' => $history->id,
                ]),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'data' => [
                'comment' => $history->load('user'),
            ]
        ]);
    }

    /**
     * Get ticket statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        
        // Only admin and disposisi can see statistics
        if ($user->role === 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Base query
        $query = Ticket::query();
        
        if ($user->role === 'disposisi') {
            // Disposisi only sees stats for tickets assigned to them
            $query->where('assigned_to', $user->id);
        }

        // Get basic counts
        $totalTickets = $query->count();
        $openTickets = (clone $query)->where('status', 'open')->count();
        $inProgressTickets = (clone $query)->where('status', 'in_progress')->count();
        $resolvedTickets = (clone $query)->where('status', 'resolved')->count();
        $closedTickets = (clone $query)->where('status', 'closed')->count();
        
        // Tickets by priority
        $ticketsByPriority = (clone $query)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
        
        // Tickets by category
        $ticketsByCategory = (clone $query)
            ->select('categories.name', DB::raw('count(*) as count'))
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->groupBy('categories.name')
            ->pluck('count', 'categories.name')
            ->toArray();
        
        // Recent tickets (last 30 days)
        $last30Days = now()->subDays(30)->startOfDay();
        $ticketsLast30Days = (clone $query)
            ->where('created_at', '>=', $last30Days)
            ->count();
        
        // Average resolution time (in hours)
        $avgResolutionTime = (clone $query)
            ->whereIn('status', ['resolved', 'closed'])
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_time')
            ->first()
            ->avg_time;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_tickets' => $totalTickets,
                'open_tickets' => $openTickets,
                'in_progress_tickets' => $inProgressTickets,
                'resolved_tickets' => $resolvedTickets,
                'closed_tickets' => $closedTickets,
                'tickets_by_priority' => $ticketsByPriority,
                'tickets_by_category' => $ticketsByCategory,
                'tickets_last_30_days' => $ticketsLast30Days,
                'avg_resolution_time' => $avgResolutionTime,
            ]
        ]);
    }

    /**
     * Restore a soft-deleted ticket.
     *
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function restore($id): JsonResponse
    {
        $user = Auth::user();
        
        // Only admin can restore tickets
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Find the deleted ticket
        $ticket = Ticket::withTrashed()->findOrFail($id);
        
        // Restore the ticket
        $ticket->restore();

        // Create ticket history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'status' => $ticket->status,
            'comments' => 'Ticket restored',
        ]);

        // Create notification for ticket creator
        Notification::create([
            'user_id' => $ticket->creator_id,
            'title' => 'Ticket Restored',
            'content' => "Your ticket #{$ticket->ticket_id} has been restored",
            'read' => false,
            'type' => 'ticket_restored',
            'data' => json_encode([
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_id,
            ]),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket restored successfully',
            'data' => [
                'ticket' => $ticket->load(['category', 'subcategory', 'creator', 'assignedTo']),
            ]
        ]);
    }
}
