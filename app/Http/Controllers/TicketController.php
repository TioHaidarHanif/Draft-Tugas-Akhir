<?php

namespace App\Http\Controllers;
use App\Exports\TicketExport;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use JsonException;

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
        $validator = Validator::make($request->all(), [
            'nama' => 'nullable|required|string|max:255',
            'nim' => 'sometimes|required_if:anonymous,false|string',
            'prodi' => 'required|string',
            'semester' => 'required|string',
            'no_hp' => 'required|string',
            'anonymous' => 'sometimes|string',
            'judul' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'deskripsi' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
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

        DB::beginTransaction();
        try {
            $ticket = new Ticket([
                'user_id' => $user->id,
                'anonymous' => $request->boolean('anonymous', false),
                'nim' => $user->nim,
                'nama' => $user->name,
                'email' => $user->email,
                'prodi' => $user->prodi,
                'semester' => $request->input('semester'),
                'no_hp' => $user->no_hp,
                'judul' => $request->input('judul'),
                'category_id' => $request->input('category_id'),
                'sub_category_id' => $request->input('sub_category_id'),
                'deskripsi' => $request->input('deskripsi'),
                'status' => 'open',
                'priority' => $request->input('priority', 'medium'),
                'read_by_student' => true,
                'read_by_admin' => false,
            ]);

            if ($ticket->anonymous) {
                $ticket->generateToken();
            }

            $ticket->save();

            if ($request->hasFile('lampiran')) {
                $file = $request->file('lampiran');
                $fileValidator = Validator::make(['lampiran' => $file], [
                    'lampiran' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
                ]);
                if ($fileValidator->fails()) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'File validation failed',
                        'errors' => $fileValidator->errors()
                    ], 422);
                }
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('attachments', $fileName, 'public');
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'file_name' => $fileName,
                    'file_type' => $file->getClientMimeType(),
                    'file_url' => asset('storage/' . $filePath),
                ]);
            }

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'action' => 'create',
                'new_status' => 'open',
                'updated_by' => $user->id,
                'timestamp' => now(),
            ]);

            $this->notificationService->createNewTicketNotification($ticket);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ticket created successfully',
                'data' => new TicketResource($ticket)
            ], 201);

        } catch (\Exception $e) {
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
    * Contoh penggunaan API:
    * 
    * GET /api/tickets
    * 
    * Query Parameters:
    * - per_page: int (default: 100)
    * - sortBy: string (created_at, status, category_id)
    * - sortOrder: string (asc, desc)
    * - user_id: int (filter by user)
    * - status: string (open, in_progress, resolved, closed)
    * - category_id: int
    * - sub_category_id: int
    * - search: string (search judul/deskripsi)
    * - startDate: date (YYYY-MM-DD)
    * - endDate: date (YYYY-MM-DD)
    *
    * Contoh:
    * GET /api/tickets?status=open&category_id=2&search=ujian&per_page=10&sortBy=created_at&sortOrder=desc
    *
    * Response:
    * {
    *   "status": "success",
    *   "data": {
    *     "tickets": [ ... ],
    *     "pagination": {
    *       "total": 25,
    *       "per_page": 10,
    *       "current_page": 1,
    *       "last_page": 3
    *     }
    *   }
    * }
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
        }
        // Admin can see all tickets

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
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
        $perPage = $request->input('per_page', 100);
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
        /**
         * Retrieves a ticket by its ID along with its related category, sub-category, attachments,
         * histories, and feedbacks. Also includes the count of associated chat messages as 'chat_count'.
         *

         */
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
            'data' =>[
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
                'errors' => $validator->errors()->all(),
                'code' => 422
            ], 422);
        }

        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);

        // Check authorization (admins and assigned disposisi can update status)
        if (
            $user->role !== 'admin' &&
            !($user->role === 'student' && $ticket->user_id === $user->id)
        ) {
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
                'errors' => $validator->errors()->all(),
                'code' => 422
            ], 422);
        }

        $user = Auth::user();
        $ticket = Ticket::findOrFail($id);

        // Check authorization (admins and assigned disposisi can update priority)
        if (
            $user->role !== 'admin' 
        ) {
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
        if (!Hash::check($request->password, $user->getAuthPassword())) {
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

    private function authorizeTicketAccess(Ticket $ticket, User $user): bool
    {
        // Admin can access any ticket
        if ($user->role === 'admin') {
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
            // Mark related notifications as read
            $this->markRelatedNotificationsAsRead($ticket, $user);
        
        } elseif ($user->role === 'student' && !$ticket->read_by_student && $ticket->user_id === $user->id) {
            $ticket->read_by_student = true;
            $ticket->save();
            // Mark related notifications as read
            $this->markRelatedNotificationsAsRead($ticket, $user);
        }
    }

    /**
     * Mark related notifications as read when a ticket is viewed
     * 
     * @param Ticket $ticket
     * @param User $user
     * @return void
     */
    private function markRelatedNotificationsAsRead(Ticket $ticket, User $user)
    {
        // Find all unread notifications for this ticket and this user
        Notification::where('recipient_id', $user->id)
            ->where('ticket_id', $ticket->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
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
            $ticket->read_by_student = false;
       
        } elseif ($currentUserRole === 'student') {
            $ticket->read_by_admin = false;
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

    
}