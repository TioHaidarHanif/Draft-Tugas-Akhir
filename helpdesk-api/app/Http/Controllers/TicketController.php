<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketDetailResource;
use App\Http\Resources\TicketResource;
use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketFeedback;
use App\Models\TicketHistory;
use App\Models\User;
use App\Services\ChatService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    /**
     * The notification service instance.
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * The chat service instance.
     *
     * @var ChatService
     */
    protected $chatService;

    /**
     * Create a new controller instance.
     *
     * @param NotificationService $notificationService
     * @param ChatService $chatService
     * @return void
     */
    public function __construct(NotificationService $notificationService, ChatService $chatService)
    {
        $this->notificationService = $notificationService;
        $this->chatService = $chatService;
    }
    /**
     * Create a new ticket
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'nim' => 'sometimes|required_if:anonymous,false|string',
            'prodi' => 'required|string',
            'semester' => 'required|string',
            'no_hp' => 'required|string',
            'anonymous' => 'sometimes|boolean',
            'judul' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'deskripsi' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }

        // Get current authenticated user
        $user = Auth::user();

        // Start transaction
        DB::beginTransaction();
        try {
            // Create ticket with user information
            $ticket = new Ticket();
            $ticket->user_id = $user->id;
            $ticket->anonymous = $request->boolean('anonymous', false);
            
            // Generate token for anonymous tickets
            if ($ticket->anonymous) {
                $ticket->generateToken();
            }
            
            // Set personal information
            $ticket->nim = $request->input('nim');
            $ticket->nama = $user->name;
            $ticket->email = $user->email;
            $ticket->prodi = $request->input('prodi');
            $ticket->semester = $request->input('semester');
            $ticket->no_hp = $request->input('no_hp');
            
            // Set ticket details
            $ticket->judul = $request->input('judul');
            $ticket->category_id = $request->input('category_id');
            $ticket->sub_category_id = $request->input('sub_category_id');
            $ticket->deskripsi = $request->input('deskripsi');
            $ticket->status = 'open';
            $ticket->priority = $request->input('priority', 'medium'); // Default priority is medium
            
            // Set read flags
            $ticket->read_by_student = true; // Creator has read it
            $ticket->read_by_admin = false;
            $ticket->read_by_disposisi = false;
            
            // Save the ticket
            $ticket->save();
            
            // Upload attachment if provided
            if ($request->hasFile('lampiran')) {
                $file = $request->file('lampiran');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('attachments', $fileName, 'public');
                
                // Create attachment record
                $attachment = new TicketAttachment();
                $attachment->ticket_id = $ticket->id;
                $attachment->file_name = $fileName;
                $attachment->file_type = $file->getClientMimeType();
                $attachment->file_url = asset('storage/' . $filePath);
                $attachment->save();
            }
            
            // Create ticket history
            $history = new TicketHistory();
            $history->ticket_id = $ticket->id;
            $history->action = 'create';
            $history->new_status = 'open';
            $history->updated_by = $user->id;
            $history->timestamp = now();
            $history->save();
            
            // Create notification for admin users
            $this->notificationService->createNewTicketNotification($ticket);
            
            // Commit transaction
            DB::commit();
            
            // Return success response with ticket data
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket created successfully',
                'data' => new TicketResource($ticket)
            ], 201);
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create ticket: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }
    
    /**
     * Get list of tickets based on user role with various filters
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::query();
        
        // Apply role-based filtering
        if ($user->role === 'student') {
            // Students can only see their own tickets
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'disposisi') {
            // Disposisi members can see tickets assigned to them
            $query->where('assigned_to', $user->id);
        }
        // Admin can see all tickets
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('created_at', [$request->startDate, $request->endDate]);
        } elseif ($request->has('startDate')) {
            $query->where('created_at', '>=', $request->startDate);
        } elseif ($request->has('endDate')) {
            $query->where('created_at', '<=', $request->endDate);
        }
        
        // Apply sorting
        $sortBy = $request->input('sortBy', 'created_at');
        $sortOrder = $request->input('sortOrder', 'desc');
        $allowedSortFields = ['created_at', 'status', 'category_id'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Include relationships
        $query->with(['category', 'subCategory', 'attachments']);
        
        // Use withCount to efficiently count chat messages using a subquery
        $query->withCount('chatMessages as chat_count');
        
        // Paginate results
        $perPage = $request->input('per_page', 10);
        $tickets = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'tickets' => TicketResource::collection($tickets),
                'pagination' => [
                    'total' => $tickets->total(),
                    'per_page' => $tickets->perPage(),
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage()
                ]
            ]
        ]);
    }
    
    /**
     * Get detailed information of a specific ticket
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        $ticket = Ticket::with(['category', 'subCategory', 'attachments', 'histories', 'feedbacks'])
            ->withCount('chatMessages as chat_count')
            ->findOrFail($id);
            
        // Check authorization
        if (!$this->authorizeTicketAccess($ticket, $user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view this ticket',
                'code' => 403
            ], 403);
        }
        
        // Update read status based on user role
        $this->updateTicketReadStatus($ticket, $user);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'ticket' => new TicketDetailResource($ticket)
            ]
        ]);
    }
    
    /**
     * Update the status of a ticket
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'sometimes|nullable|in:low,medium,high,urgent',
            'comment' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);
        
        // Check authorization (admins and assigned disposisi can update status)
        if ($user->role !== 'admin' && 
            !($user->role === 'disposisi' && $ticket->assigned_to === $user->id) &&
            !($user->role === 'student' && $ticket->user_id === $user->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this ticket',
                'code' => 403
            ], 403);
        }
        
        // Student can only close tickets
        if ($user->role === 'student' && $request->status !== 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Students can only close tickets',
                'code' => 403
            ], 403);
        }
        
        DB::beginTransaction();
        try {
            $oldStatus = $ticket->status;
            $newStatus = $request->status;
            $oldPriority = $ticket->priority;
            $newPriority = $request->input('priority', $oldPriority);
            
            // Update ticket status and priority
            $ticket->status = $newStatus;
            $ticket->priority = $newPriority;
            $ticket->save();
            
            // Create ticket history
            $history = new TicketHistory();
            $history->ticket_id = $ticket->id;
            $history->action = 'status_change';
            $history->old_status = $oldStatus;
            $history->new_status = $newStatus;
            $history->old_priority = $oldPriority;
            $history->new_priority = $newPriority;
            $history->updated_by = $user->id;
            $history->timestamp = now();
            $history->save();
            
            // Add comment if provided
            if ($request->has('comment') && !empty($request->comment)) {
                $feedback = new TicketFeedback();
                $feedback->ticket_id = $ticket->id;
                $feedback->created_by = $user->id;
                $feedback->text = $request->comment;
                $feedback->created_by_role = $user->role;
                $feedback->save();
            }
            
            // Create notifications based on who changed the status
            $this->notificationService->createStatusChangeNotification($ticket, $oldStatus, $newStatus, $user->id);
            
            // Reset read flags for other roles
            $this->resetTicketReadFlags($ticket, $user->role);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket status updated successfully',
                'data' => new TicketResource($ticket)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update ticket status: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }
    
    /**
     * Update the priority of a specific ticket
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePriority(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'priority' => 'required|in:low,medium,high,urgent',
            'comment' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);
        
        // Check authorization (admins and assigned disposisi can update priority)
        if ($user->role !== 'admin' && 
            !($user->role === 'disposisi' && $ticket->assigned_to === $user->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this ticket priority',
                'code' => 403
            ], 403);
        }
        
        DB::beginTransaction();
        try {
            $oldPriority = $ticket->priority;
            $newPriority = $request->priority;
            
            // Update ticket priority
            $ticket->priority = $newPriority;
            $ticket->save();
            
            // Create ticket history
            $history = new TicketHistory();
            $history->ticket_id = $ticket->id;
            $history->action = 'priority_change';
            $history->old_priority = $oldPriority;
            $history->new_priority = $newPriority;
            $history->comment = $request->comment ?? "Priority changed from $oldPriority to $newPriority";
            $history->updated_by = $user->id;
            $history->timestamp = now();
            $history->save();
            
            // Create notification for priority change
            $this->notificationService->createTicketUpdateNotification(
                $ticket,
                'priority_change',
                "Ticket priority changed from $oldPriority to $newPriority",
                $user->id
            );
            
            // Commit transaction
            DB::commit();
            
            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket priority updated successfully',
                'data' => new TicketDetailResource($ticket)
            ]);
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update ticket priority: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }
    
    /**
     * Assign a ticket to a disposisi member (Admin only)
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        
        $user = Auth::user();
        
        // Only admin can assign tickets
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only administrators can assign tickets',
                'code' => 403
            ], 403);
        }
        
        $ticket = Ticket::findOrFail($id);
        $assignedUser = User::findOrFail($request->assigned_to);
        
        // Check if assigned user is a disposisi member
        if ($assignedUser->role !== 'disposisi') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tickets can only be assigned to disposisi members',
                'code' => 422
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Update ticket with assigned user
            $ticket->assigned_to = $assignedUser->id;
            $ticket->status = 'in_progress';
            $ticket->save();
            
            // Create ticket history
            $history = new TicketHistory();
            $history->ticket_id = $ticket->id;
            $history->action = 'assignment';
            $history->assigned_by = $user->id;
            $history->assigned_to = $assignedUser->id;
            $history->timestamp = now();
            $history->save();
            
            // Create notification for assigned user
            $this->notificationService->createAssignmentNotification($ticket, $user->id, $assignedUser->id);
            
            // Update read flags
            $ticket->read_by_admin = true;
            $ticket->read_by_disposisi = false;
            $ticket->save();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket assigned successfully',
                'data' => [
                    'id' => $ticket->id,
                    'assigned_to' => $ticket->assigned_to,
                    'updated_at' => $ticket->updated_at,
                    'ticket_history' => [
                        'id' => $history->id,
                        'action' => $history->action,
                        'assigned_by' => $history->assigned_by,
                        'assigned_to' => $history->assigned_to,
                        'timestamp' => $history->timestamp
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign ticket: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }
    
    /**
     * Get ticket statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $user = Auth::user();
        $query = Ticket::query();
        
        // Apply role-based filtering
        if ($user->role === 'student') {
            // Students can only see stats for their own tickets
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'disposisi') {
            // Disposisi members can see stats for tickets assigned to them
            $query->where('assigned_to', $user->id);
        }
        
        // Calculate stats
        $totalTickets = (clone $query)->count();
        $newTickets = (clone $query)->where('status', 'open')->count();
        $inProgressTickets = (clone $query)->where('status', 'in_progress')->count();
        $resolvedTickets = (clone $query)->where('status', 'resolved')->count();
        $closedTickets = (clone $query)->where('status', 'closed')->count();
        
        // Calculate unread based on user role
        $unreadField = 'read_by_' . $user->role;
        $unreadTickets = (clone $query)->where($unreadField, false)->count();
        
        // Get category distribution
        $categoryStats = DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->select('categories.id as category_id', 'categories.name as category_name', DB::raw('count(*) as count'))
            ->when($user->role === 'student', function ($query) use ($user) {
                return $query->where('tickets.user_id', $user->id);
            })
            ->when($user->role === 'disposisi', function ($query) use ($user) {
                return $query->where('tickets.assigned_to', $user->id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $totalTickets,
                'new' => $newTickets,
                'in_progress' => $inProgressTickets,
                'resolved' => $resolvedTickets,
                'closed' => $closedTickets,
                'unread' => $unreadTickets,
                'by_category' => $categoryStats
            ]
        ]);
    }
    
    /**
     * Add a feedback/comment to a ticket
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addFeedback(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);
        
        // Check authorization
        if (!$this->authorizeTicketAccess($ticket, $user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to add feedback to this ticket',
                'code' => 403
            ], 403);
        }
        
        DB::beginTransaction();
        try {
            // Create feedback
            $feedback = new TicketFeedback();
            $feedback->ticket_id = $ticket->id;
            $feedback->created_by = $user->id;
            $feedback->text = $request->text;
            $feedback->created_by_role = $user->role;
            $feedback->save();
            
            // Create notifications based on the feedback sender
            $this->notificationService->createFeedbackNotification($ticket, $user->id);
            
            // Reset read flags for other roles
            $this->resetTicketReadFlags($ticket, $user->role);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Feedback added successfully',
                'data' => [
                    'id' => $feedback->id,
                    'ticket_id' => $feedback->ticket_id,
                    'created_by' => $feedback->created_by,
                    'text' => $feedback->text,
                    'created_by_role' => $feedback->created_by_role,
                    'created_at' => $feedback->created_at
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add feedback: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }
    
    /**
     * Soft delete a ticket
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);
        
        // Only admins or the ticket creator can delete tickets
        if ($user->role !== 'admin' && $ticket->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this ticket',
                'code' => 403
            ], 403);
        }
        
        try {
            $ticket->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket has been soft deleted',
                'data' => [
                    'id' => $ticket->id,
                    'deleted_at' => $ticket->deleted_at
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete ticket: ' . $e->getMessage(),
                'code' => 400
            ], 400);
        }
    }
    
    /**
     * Restore a previously soft-deleted ticket
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        $user = Auth::user();
        
        // Only admins can restore tickets
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only administrators can restore tickets',
                'code' => 403
            ], 403);
        }
        
        try {
            $ticket = Ticket::withTrashed()->findOrFail($id);
            $ticket->restore();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket has been restored',
                'data' => [
                    'id' => $ticket->id,
                    'deleted_at' => $ticket->deleted_at
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore ticket: ' . $e->getMessage(),
                'code' => 400
            ], 400);
        }
    }
    
    /**
     * Reveal the token for an anonymous ticket after password verification
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function revealToken(Request $request, $id)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }

        // Get the ticket
        $ticket = Ticket::findOrFail($id);
        
        // Check if the ticket is anonymous and has a token
        if (!$ticket->anonymous || empty($ticket->token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token is only available for anonymous tickets',
                'code' => 400
            ], 400);
        }
        
        // Get current authenticated user
        $user = Auth::user();
        
        // If admin, allow access without password verification
        if ($user->role === 'admin') {
            // Store in session that token has been revealed
            session(['revealed_token_' . $ticket->id => true]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Token revealed successfully',
                'data' => [
                    'token' => $ticket->token
                ]
            ]);
        }
        
        // Verify if this is the ticket creator by checking user_id
        if ($ticket->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view this ticket token',
                'code' => 403
            ], 403);
        }
        
        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid password',
                'code' => 401
            ], 401);
        }
        
        // Password verified, reveal token
        // Store in session that token has been revealed
        session(['revealed_token_' . $ticket->id => true]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Token revealed successfully',
            'data' => [
                'token' => $ticket->token
            ]
        ]);
    }
    
    /**
     * Create notifications for status changes
     * 
     * @param Ticket $ticket
     * @param User $user
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    private function createStatusChangeNotifications(Ticket $ticket, User $user, string $oldStatus, string $newStatus)
    {
        // Common notification message
        $message = "Status tiket telah diperbarui dari {$oldStatus} menjadi {$newStatus}";
        
        if ($user->role === 'admin' || $user->role === 'disposisi') {
            // Notify the student (ticket creator)
            if ($ticket->user_id) {
                $this->createTicketNotification(
                    $ticket->user_id,
                    'student',
                    $user->id,
                    $ticket->id,
                    'Status Tiket Diperbarui',
                    $message,
                    'status_change'
                );
            }
            
            // If changed by disposisi, also notify admin
            if ($user->role === 'disposisi') {
                $this->createTicketNotification(
                    null,
                    'admin',
                    $user->id,
                    $ticket->id,
                    'Status Tiket Diperbarui',
                    $message,
                    'status_change'
                );
            }
            
            // If changed by admin and ticket is assigned, notify disposisi
            if ($user->role === 'admin' && $ticket->assigned_to) {
                $this->createTicketNotification(
                    $ticket->assigned_to,
                    'disposisi',
                    $user->id,
                    $ticket->id,
                    'Status Tiket Diperbarui',
                    $message,
                    'status_change'
                );
            }
        } elseif ($user->role === 'student') {
            // Student updated status (can only close tickets)
            // Notify admin
            $this->createTicketNotification(
                null,
                'admin',
                $user->id,
                $ticket->id,
                'Status Tiket Diperbarui',
                $message,
                'status_change'
            );
            
            // Notify assigned disposisi if any
            if ($ticket->assigned_to) {
                $this->createTicketNotification(
                    $ticket->assigned_to,
                    'disposisi',
                    $user->id,
                    $ticket->id,
                    'Status Tiket Diperbarui',
                    $message,
                    'status_change'
                );
            }
        }
    }
    
    /**
     * Create notifications for feedback
     * 
     * @param Ticket $ticket
     * @param User $user
     * @param TicketFeedback $feedback
     * @return void
     */
    private function createFeedbackNotifications(Ticket $ticket, User $user, TicketFeedback $feedback)
    {
        $title = 'Feedback Baru';
        $message = "Feedback baru untuk tiket: {$ticket->judul}";
        
        if ($user->role === 'admin' || $user->role === 'disposisi') {
            // Notify the student (ticket creator)
            if ($ticket->user_id) {
                $this->createTicketNotification(
                    $ticket->user_id,
                    'student',
                    $user->id,
                    $ticket->id,
                    $title,
                    $message,
                    'feedback'
                );
            }
        } elseif ($user->role === 'student') {
            // Notify admin
            $this->createTicketNotification(
                null,
                'admin',
                $user->id,
                $ticket->id,
                $title,
                $message,
                'feedback'
            );
            
            // Notify assigned disposisi if any
            if ($ticket->assigned_to) {
                $this->createTicketNotification(
                    $ticket->assigned_to,
                    'disposisi',
                    $user->id,
                    $ticket->id,
                    $title,
                    $message,
                    'feedback'
                );
            }
        }
    }
    
    /**
     * Check if user is authorized to access a ticket
     * 
     * @param Ticket $ticket
     * @param User $user
     * @return bool
     */
    private function authorizeTicketAccess(Ticket $ticket, User $user)
    {
        // Admin can access any ticket
        if ($user->role === 'admin') {
            return true;
        }
        
        // Disposisi can access tickets assigned to them
        if ($user->role === 'disposisi' && $ticket->assigned_to === $user->id) {
            return true;
        }
        
        // Students can only access their own tickets
        if ($user->role === 'student' && $ticket->user_id === $user->id) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Update read status of a ticket based on user role
     * 
     * @param Ticket $ticket
     * @param User $user
     * @return void
     */
    private function updateTicketReadStatus(Ticket $ticket, User $user)
    {
        if ($user->role === 'admin' && !$ticket->read_by_admin) {
            $ticket->read_by_admin = true;
            $ticket->save();
        } elseif ($user->role === 'disposisi' && !$ticket->read_by_disposisi && $ticket->assigned_to === $user->id) {
            $ticket->read_by_disposisi = true;
            $ticket->save();
        } elseif ($user->role === 'student' && !$ticket->read_by_student && $ticket->user_id === $user->id) {
            $ticket->read_by_student = true;
            $ticket->save();
        }
    }
    
    /**
     * Reset read flags for other roles when a ticket is updated
     * 
     * @param Ticket $ticket
     * @param string $currentUserRole
     * @return void
     */
    private function resetTicketReadFlags(Ticket $ticket, $currentUserRole)
    {
        if ($currentUserRole === 'admin') {
            $ticket->read_by_disposisi = false;
            $ticket->read_by_student = false;
        } elseif ($currentUserRole === 'disposisi') {
            $ticket->read_by_admin = false;
            $ticket->read_by_student = false;
        } elseif ($currentUserRole === 'student') {
            $ticket->read_by_admin = false;
            $ticket->read_by_disposisi = false;
        }
        
        $ticket->save();
    }
    
    /**
     * Format ticket response data for listing
     * 
     * @param Ticket $ticket
     * @return array
     */
    private function formatTicketResponse(Ticket $ticket)
    {
        $response = [
            'id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'nama' => $ticket->anonymous ? '[Anonymous]' : $ticket->nama,
            'nim' => $ticket->anonymous ? null : $ticket->nim,
            'prodi' => $ticket->prodi,
            'semester' => $ticket->semester,
            'email' => $ticket->anonymous ? null : $ticket->email,
            'no_hp' => $ticket->anonymous ? null : $ticket->no_hp,
            'category_id' => $ticket->category_id,
            'sub_category_id' => $ticket->sub_category_id,
            'judul' => $ticket->judul,
            'deskripsi' => $ticket->deskripsi,
            'anonymous' => $ticket->anonymous,
            'status' => $ticket->status,
            'assigned_to' => $ticket->assigned_to,
            'read_by_admin' => $ticket->read_by_admin,
            'read_by_disposisi' => $ticket->read_by_disposisi,
            'read_by_student' => $ticket->read_by_student,
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at
        ];
        
        // Include token for admin users or if token has been revealed
        $user = Auth::user();
        if ($ticket->anonymous && $ticket->token && ($user->role === 'admin' || session('revealed_token_' . $ticket->id))) {
            $response['token'] = $ticket->token;
        }
        
        // Include attachments if available
        if ($ticket->relationLoaded('attachments') && $ticket->attachments->count() > 0) {
            $response['lampiran'] = [
                'id' => $ticket->attachments->first()->id,
                'file_name' => $ticket->attachments->first()->file_name,
                'file_type' => $ticket->attachments->first()->file_type,
                'file_url' => $ticket->attachments->first()->file_url
            ];
        }
        
        return $response;
    }
    
    /**
     * Format ticket response data for detailed view
     * 
     * @param Ticket $ticket
     * @return array
     */
    private function formatTicketDetailResponse(Ticket $ticket)
    {
        $response = $this->formatTicketResponse($ticket);
        
        // Include histories and feedbacks
        if ($ticket->relationLoaded('histories')) {
            $response['ticket_histories'] = $ticket->histories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'action' => $history->action,
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'assigned_by' => $history->assigned_by,
                    'assigned_to' => $history->assigned_to,
                    'updated_by' => $history->updated_by,
                    'timestamp' => $history->timestamp
                ];
            });
        }
        
        if ($ticket->relationLoaded('feedbacks')) {
            $response['ticket_feedbacks'] = $ticket->feedbacks->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'created_by' => $feedback->created_by,
                    'text' => $feedback->text,
                    'created_by_role' => $feedback->created_by_role,
                    'created_at' => $feedback->created_at
                ];
            });
        }
        
        return $response;
    }
}