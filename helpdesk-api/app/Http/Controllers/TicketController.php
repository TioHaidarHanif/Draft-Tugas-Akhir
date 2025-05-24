<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\AddTicketFeedbackRequest;
use App\Http\Requests\TicketListFilterRequest;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketFeedback;
use App\Models\TicketAttachment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    // POST /tickets
    public function store(StoreTicketRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $data = $request->validated();
            $data['user_id'] = $user->id;
            $data['nama'] = $user->name;
            $data['email'] = $user->email;
            $data['nim'] = $user->role === 'student' ? $user->nim ?? null : null;
            $data['status'] = 'open';
            $data['read_by_admin'] = false;
            $data['read_by_disposisi'] = false;
            $data['read_by_student'] = false;
            $ticket = Ticket::create($data);
            // Lampiran (support multiple files)
            $attachments = [];
            $lampiranFiles = $request->file('lampiran');
            if ($lampiranFiles) {
                if (!is_array($lampiranFiles)) {
                    $lampiranFiles = [$lampiranFiles];
                }
                foreach ($lampiranFiles as $file) {
                    $path = $file->store('lampiran', 'public');
                    $attachments[] = TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_url' => asset('storage/' . $path),
                    ]);
                }
            }
            // Notifikasi otomatis ke admin
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'recipient_id' => $admin->id,
                    'recipient_role' => 'admin',
                    'sender_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'title' => 'Tiket Baru',
                    'message' => 'Tiket baru telah dibuat: ' . $ticket->judul,
                    'type' => 'new_ticket',
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket created successfully',
                'data' => $ticket->load('attachments'),
            ], 201);
        });
    }

    // GET /tickets
    public function index(TicketListFilterRequest $request)
    {
        $user = $request->user();
        $query = Ticket::query();
        // Filter by role
        if ($user->role === 'student') {
            $query->where('user_id', $user->id);
        }
        // Filter
        $filters = $request->validated();
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['category_id'])) $query->where('category_id', $filters['category_id']);
        if (!empty($filters['sub_category_id'])) $query->where('sub_category_id', $filters['sub_category_id']);
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('judul', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('deskripsi', 'like', '%' . $filters['search'] . '%');
            });
        }
        if (!empty($filters['startDate'])) $query->whereDate('created_at', '>=', $filters['startDate']);
        if (!empty($filters['endDate'])) $query->whereDate('created_at', '<=', $filters['endDate']);
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortOrder = $filters['sortOrder'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = $filters['per_page'] ?? 10;
        $tickets = $query->with(['attachment'])->paginate($perPage);
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

    // GET /tickets/{id}
    public function show($id)
    {
        $ticket = Ticket::with(['attachment', 'histories', 'feedbacks'])->findOrFail($id);
        $user = Auth::user();
        if ($user->role === 'student' && $ticket->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden', 'code' => 403], 403);
        }
        return response()->json([
            'status' => 'success',
            'data' => [ 'ticket' => $ticket ]
        ]);
    }

    // PATCH /tickets/{id}/status
    public function updateStatus(UpdateTicketStatusRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $ticket = Ticket::findOrFail($id);
            $user = $request->user();
            $oldStatus = $ticket->status;
            $ticket->status = $request->input('status');
            $ticket->save();
            // Catat riwayat
            $history = TicketHistory::create([
                'ticket_id' => $ticket->id,
                'action' => 'status_change',
                'old_status' => $oldStatus,
                'new_status' => $ticket->status,
                'updated_by' => $user->id,
                'timestamp' => now(),
            ]);
            // Notifikasi otomatis
            $recipients = [];
            if (in_array($user->role, ['admin', 'disposisi'])) {
                $recipients[] = $ticket->user_id; // notify student
            }
            if ($user->role === 'disposisi') {
                $recipients[] = $ticket->assigned_to; // notify admin
            }
            if ($user->role === 'admin' && $ticket->assigned_to) {
                $recipients[] = $ticket->assigned_to;
            }
            foreach (array_filter($recipients) as $recipientId) {
                $recipientUser = \App\Models\User::find($recipientId);
                Notification::create([
                    'recipient_id' => $recipientId,
                    'recipient_role' => $recipientUser ? $recipientUser->role : null,
                    'sender_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'title' => 'Status Tiket Diperbarui',
                    'message' => 'Status tiket telah diperbarui dari ' . $oldStatus . ' menjadi ' . $ticket->status,
                    'type' => 'status_change',
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket status updated successfully',
                'data' => [
                    'id' => $ticket->id,
                    'status' => $ticket->status,
                    'updated_at' => $ticket->updated_at,
                    'ticket_history' => $history,
                ]
            ]);
        });
    }

    // POST /tickets/{id}/assign
    public function assign(AssignTicketRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $ticket = Ticket::findOrFail($id);
            $user = $request->user();
            $oldAssigned = $ticket->assigned_to;
            $ticket->assigned_to = $request->input('assigned_to');
            $ticket->save();
            // Catat riwayat
            $history = TicketHistory::create([
                'ticket_id' => $ticket->id,
                'action' => 'assignment',
                'assigned_by' => $user->id,
                'assigned_to' => $ticket->assigned_to,
                'timestamp' => now(),
            ]);
            // Notifikasi otomatis ke disposisi
            $recipientUser = \App\Models\User::find($ticket->assigned_to);
            Notification::create([
                'recipient_id' => $ticket->assigned_to,
                'recipient_role' => $recipientUser ? $recipientUser->role : 'disposisi',
                'sender_id' => $user->id,
                'ticket_id' => $ticket->id,
                'title' => 'Tiket Didisposisikan',
                'message' => 'Tiket telah didisposisikan kepada Anda: ' . $ticket->judul,
                'type' => 'assignment',
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Ticket assigned successfully',
                'data' => [
                    'id' => $ticket->id,
                    'assigned_to' => $ticket->assigned_to,
                    'updated_at' => $ticket->updated_at,
                    'ticket_history' => $history,
                ]
            ]);
        });
    }

    // GET /tickets/statistics
    public function statistics(Request $request)
    {
        $user = $request->user();
        $query = Ticket::query();
        if ($user->role === 'student') {
            $query->where('user_id', $user->id);
        }
        $total = $query->count();
        $new = (clone $query)->where('status', 'new')->count();
        $in_progress = (clone $query)->where('status', 'in_progress')->count();
        $resolved = (clone $query)->where('status', 'resolved')->count();
        $closed = (clone $query)->where('status', 'closed')->count();
        $unread = (clone $query)->where(function($q) use ($user) {
            if ($user->role === 'admin') $q->where('read_by_admin', false);
            if ($user->role === 'disposisi') $q->where('read_by_disposisi', false);
            if ($user->role === 'student') $q->where('read_by_student', false);
        })->count();
        $by_category = $query->select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')->get()->map(function($row) {
                $cat = \App\Models\Category::find($row->category_id);
                return [
                    'category_id' => $row->category_id,
                    'category_name' => $cat ? $cat->name : null,
                    'count' => $row->count
                ];
            });
        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $total,
                'new' => $new,
                'in_progress' => $in_progress,
                'resolved' => $resolved,
                'closed' => $closed,
                'unread' => $unread,
                'by_category' => $by_category,
            ]
        ]);
    }

    // POST /tickets/{id}/feedback
    public function addFeedback(AddTicketFeedbackRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $ticket = Ticket::findOrFail($id);
            $user = $request->user();
            $feedback = TicketFeedback::create([
                'ticket_id' => $ticket->id,
                'created_by' => $user->id,
                'text' => $request->input('text'),
                'created_by_role' => $user->role,
            ]);
            // Notifikasi otomatis
            if (in_array($user->role, ['admin', 'disposisi'])) {
                Notification::create([
                    'recipient_id' => $ticket->user_id,
                    'sender_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'title' => 'Feedback Baru',
                    'message' => 'Feedback baru untuk tiket: ' . $ticket->judul,
                    'type' => 'feedback',
                ]);
            } else if ($user->role === 'student') {
                // ke admin dan disposisi
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    Notification::create([
                        'recipient_id' => $admin->id,
                        'sender_id' => $user->id,
                        'ticket_id' => $ticket->id,
                        'title' => 'Feedback Baru',
                        'message' => 'Feedback baru untuk tiket: ' . $ticket->judul,
                        'type' => 'feedback',
                    ]);
                }
                if ($ticket->assigned_to) {
                    Notification::create([
                        'recipient_id' => $ticket->assigned_to,
                        'sender_id' => $user->id,
                        'ticket_id' => $ticket->id,
                        'title' => 'Feedback Baru',
                        'message' => 'Feedback baru untuk tiket: ' . $ticket->judul,
                        'type' => 'feedback',
                    ]);
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Feedback added successfully',
                'data' => $feedback
            ], 201);
        });
    }

    // DELETE /tickets/{id}
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();
        if ($user->role === 'student' && $ticket->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden', 'code' => 403], 403);
        }
        $ticket->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Ticket has been soft deleted',
            'data' => [
                'id' => $ticket->id,
                'deleted_at' => $ticket->deleted_at
            ]
        ]);
    }

    // POST /tickets/{id}/restore
    public function restore($id)
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $user = Auth::user();
        if ($user->role === 'student' && $ticket->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden', 'code' => 403], 403);
        }
        $ticket->restore();
        return response()->json([
            'status' => 'success',
            'message' => 'Ticket has been restored',
            'data' => [
                'id' => $ticket->id,
                'deleted_at' => null
            ]
        ]);
    }
}
