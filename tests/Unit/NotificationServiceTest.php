<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var NotificationService */
    private $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
    }

    /**
     * Test creating a notification for a new ticket.
     *
     * @return void
     */
    public function test_create_new_ticket_notification()
    {
        // Create an admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a student user
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $student->id,
            'judul' => 'Test Ticket'
        ]);
        
        // Create notification for new ticket
        $this->notificationService->createNewTicketNotification($ticket);
        
        // Check if notification was created for admin
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $admin->id,
            'recipient_role' => 'admin',
            'sender_id' => $student->id,
            'ticket_id' => $ticket->id,
            'title' => 'Tiket Baru',
            'message' => "Tiket baru telah dibuat: {$ticket->judul}",
            'type' => 'new_ticket'
        ]);
    }
    
    /**
     * Test creating a notification for a ticket assignment.
     *
     * @return void
     */
    public function test_create_assignment_notification()
    {
        // Create users
        $admin = User::factory()->create(['role' => 'admin']);
        $disposisi = User::factory()->create(['role' => 'disposisi']);
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $student->id,
            'judul' => 'Test Ticket'
        ]);
        
        // Create notification for ticket assignment
        $this->notificationService->createAssignmentNotification($ticket, $admin->id, $disposisi->id);
        
        // Check if notification was created for disposisi
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $disposisi->id,
            'recipient_role' => 'disposisi',
            'sender_id' => $admin->id,
            'ticket_id' => $ticket->id,
            'title' => 'Tiket Didisposisikan',
            'message' => "Tiket telah didisposisikan kepada Anda: {$ticket->judul}",
            'type' => 'assignment'
        ]);
    }
    
    /**
     * Test creating a notification for a status change.
     *
     * @return void
     */
    public function test_create_status_change_notification()
    {
        // Create users
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $student->id,
            'judul' => 'Test Ticket'
        ]);
        
        // Create notification for status change (admin updates status)
        $this->notificationService->createStatusChangeNotification($ticket, 'new', 'in_progress', $admin->id);
        
        // Check if notification was created for student
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $student->id,
            'recipient_role' => 'student',
            'sender_id' => $admin->id,
            'ticket_id' => $ticket->id,
            'title' => 'Status Tiket Diperbarui',
            'message' => "Status tiket telah diperbarui dari new menjadi in_progress",
            'type' => 'status_change'
        ]);
    }
    
    /**
     * Test creating a notification for a new feedback.
     *
     * @return void
     */
    public function test_create_feedback_notification()
    {
        // Create users
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $student->id,
            'judul' => 'Test Ticket'
        ]);
        
        // Create notification for new feedback (admin adds feedback)
        $this->notificationService->createFeedbackNotification($ticket, $admin->id);
        
        // Check if notification was created for student
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $student->id,
            'recipient_role' => 'student',
            'sender_id' => $admin->id,
            'ticket_id' => $ticket->id,
            'title' => 'Feedback Baru',
            'message' => "Feedback baru untuk tiket: {$ticket->judul}",
            'type' => 'feedback'
        ]);
    }
}
