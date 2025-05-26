<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Create a notification for a new ticket.
     *
     * @param Ticket $ticket
     * @return void
     */
    public function createNewTicketNotification(Ticket $ticket)
    {
        // Notify all admin users about the new ticket
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            Notification::create([
                'recipient_id' => $admin->id,
                'recipient_role' => 'admin',
                'sender_id' => $ticket->user_id,
                'ticket_id' => $ticket->id,
                'title' => 'Tiket Baru',
                'message' => "Tiket baru telah dibuat: {$ticket->judul}",
                'type' => 'new_ticket'
            ]);
        }
    }
    
    /**
     * Create a notification for a ticket assignment.
     *
     * @param Ticket $ticket
     * @param string $assignedById
     * @param string $assignedToId
     * @return void
     */
    public function createAssignmentNotification(Ticket $ticket, $assignedById, $assignedToId)
    {
        // Get the assigned user
        $assignedTo = User::find($assignedToId);
        
        if ($assignedTo) {
            Notification::create([
                'recipient_id' => $assignedToId,
                'recipient_role' => 'disposisi',
                'sender_id' => $assignedById,
                'ticket_id' => $ticket->id,
                'title' => 'Tiket Didisposisikan',
                'message' => "Tiket telah didisposisikan kepada Anda: {$ticket->judul}",
                'type' => 'assignment'
            ]);
        }
    }
    
    /**
     * Create a notification for a ticket status change.
     *
     * @param Ticket $ticket
     * @param string $oldStatus
     * @param string $newStatus
     * @param string $updatedById
     * @return void
     */
    public function createStatusChangeNotification(Ticket $ticket, $oldStatus, $newStatus, $updatedById)
    {
        $updatedBy = User::find($updatedById);
        
        if (!$updatedBy) {
            return;
        }
        
        $message = "Status tiket telah diperbarui dari {$oldStatus} menjadi {$newStatus}";
        
        // Determine recipients based on who updated the status
        if ($updatedBy->role === 'admin') {
            // If admin updated, notify student and assigned disposisi
            $this->notifyStudentAboutStatusChange($ticket, $message, $updatedById);
            
            if ($ticket->assigned_to) {
                $this->notifyUserAboutStatusChange($ticket, $message, $updatedById, $ticket->assigned_to);
            }
        } elseif ($updatedBy->role === 'disposisi') {
            // If disposisi updated, notify student and admin
            $this->notifyStudentAboutStatusChange($ticket, $message, $updatedById);
            $this->notifyAdminsAboutStatusChange($ticket, $message, $updatedById);
        } else {
            // If student updated, notify admin and assigned disposisi
            $this->notifyAdminsAboutStatusChange($ticket, $message, $updatedById);
            
            if ($ticket->assigned_to) {
                $this->notifyUserAboutStatusChange($ticket, $message, $updatedById, $ticket->assigned_to);
            }
        }
    }
    
    /**
     * Create a notification for a new feedback.
     *
     * @param Ticket $ticket
     * @param string $feedbackById
     * @return void
     */
    public function createFeedbackNotification(Ticket $ticket, $feedbackById)
    {
        $feedbackBy = User::find($feedbackById);
        
        if (!$feedbackBy) {
            return;
        }
        
        $message = "Feedback baru untuk tiket: {$ticket->judul}";
        
        // Determine recipients based on who added the feedback
        if ($feedbackBy->role === 'admin' || $feedbackBy->role === 'disposisi') {
            // If admin or disposisi added feedback, notify student
            $this->notifyStudentAboutFeedback($ticket, $message, $feedbackById);
        } else {
            // If student added feedback, notify admin and assigned disposisi
            $this->notifyAdminsAboutFeedback($ticket, $message, $feedbackById);
            
            if ($ticket->assigned_to) {
                $this->notifyUserAboutFeedback($ticket, $message, $feedbackById, $ticket->assigned_to);
            }
        }
    }
    
    /**
     * Notify the student (ticket creator) about a status change.
     *
     * @param Ticket $ticket
     * @param string $message
     * @param string $updatedById
     * @return void
     */
    private function notifyStudentAboutStatusChange(Ticket $ticket, $message, $updatedById)
    {
        Notification::create([
            'recipient_id' => $ticket->user_id,
            'recipient_role' => 'student',
            'sender_id' => $updatedById,
            'ticket_id' => $ticket->id,
            'title' => 'Status Tiket Diperbarui',
            'message' => $message,
            'type' => 'status_change'
        ]);
    }
    
    /**
     * Notify all admin users about a status change.
     *
     * @param Ticket $ticket
     * @param string $message
     * @param string $updatedById
     * @return void
     */
    private function notifyAdminsAboutStatusChange(Ticket $ticket, $message, $updatedById)
    {
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            // Skip notifying the user who made the change
            if ($admin->id === $updatedById) {
                continue;
            }
            
            Notification::create([
                'recipient_id' => $admin->id,
                'recipient_role' => 'admin',
                'sender_id' => $updatedById,
                'ticket_id' => $ticket->id,
                'title' => 'Status Tiket Diperbarui',
                'message' => $message,
                'type' => 'status_change'
            ]);
        }
    }
    
    /**
     * Notify a specific user about a status change.
     *
     * @param Ticket $ticket
     * @param string $message
     * @param string $updatedById
     * @param string $userId
     * @return void
     */
    private function notifyUserAboutStatusChange(Ticket $ticket, $message, $updatedById, $userId)
    {
        $user = User::find($userId);
        
        if (!$user || $user->id === $updatedById) {
            return;
        }
        
        Notification::create([
            'recipient_id' => $userId,
            'recipient_role' => $user->role,
            'sender_id' => $updatedById,
            'ticket_id' => $ticket->id,
            'title' => 'Status Tiket Diperbarui',
            'message' => $message,
            'type' => 'status_change'
        ]);
    }
    
    /**
     * Notify the student (ticket creator) about a new feedback.
     *
     * @param Ticket $ticket
     * @param string $message
     * @param string $feedbackById
     * @return void
     */
    private function notifyStudentAboutFeedback(Ticket $ticket, $message, $feedbackById)
    {
        Notification::create([
            'recipient_id' => $ticket->user_id,
            'recipient_role' => 'student',
            'sender_id' => $feedbackById,
            'ticket_id' => $ticket->id,
            'title' => 'Feedback Baru',
            'message' => $message,
            'type' => 'feedback'
        ]);
    }
    
    /**
     * Notify all admin users about a new feedback.
     *
     * @param Ticket $ticket
     * @param string $message
     * @param string $feedbackById
     * @return void
     */
    private function notifyAdminsAboutFeedback(Ticket $ticket, $message, $feedbackById)
    {
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            // Skip notifying the user who added the feedback
            if ($admin->id === $feedbackById) {
                continue;
            }
            
            Notification::create([
                'recipient_id' => $admin->id,
                'recipient_role' => 'admin',
                'sender_id' => $feedbackById,
                'ticket_id' => $ticket->id,
                'title' => 'Feedback Baru',
                'message' => $message,
                'type' => 'feedback'
            ]);
        }
    }
    
    /**
     * Notify a specific user about a new feedback.
     *
     * @param Ticket $ticket
     * @param string $message
     * @param string $feedbackById
     * @param string $userId
     * @return void
     */
    private function notifyUserAboutFeedback(Ticket $ticket, $message, $feedbackById, $userId)
    {
        $user = User::find($userId);
        
        if (!$user || $user->id === $feedbackById) {
            return;
        }
        
        Notification::create([
            'recipient_id' => $userId,
            'recipient_role' => $user->role,
            'sender_id' => $feedbackById,
            'ticket_id' => $ticket->id,
            'title' => 'Feedback Baru',
            'message' => $message,
            'type' => 'feedback'
        ]);
    }
}
