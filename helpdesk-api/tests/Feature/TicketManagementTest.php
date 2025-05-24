<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;
    protected $studentUser;
    protected $disposisiUser;
    protected $category;
    protected $subCategory;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create storage disk for testing
        Storage::fake('public');
        
        // Create test users
        $this->adminUser = User::factory()->create([
            'role' => 'admin'
        ]);
        
        $this->studentUser = User::factory()->create([
            'role' => 'student'
        ]);
        
        $this->disposisiUser = User::factory()->create([
            'role' => 'disposisi'
        ]);
        
        // Create a test category and subcategory
        $this->category = Category::create([
            'name' => 'Test Category'
        ]);
        
        $this->subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Test SubCategory'
        ]);
    }

    /**
     * Test creating a new ticket
     */
    public function test_student_can_create_ticket(): void
    {
        $ticketData = [
            'nim' => '1234567890',
            'prodi' => 'Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
            'judul' => 'Test Ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'deskripsi' => 'This is a test ticket description',
            'anonymous' => false
        ];
        
        $response = $this->actingAs($this->studentUser)
            ->postJson('/api/tickets', $ticketData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'nama',
                    'nim',
                    'prodi',
                    'semester',
                    'email',
                    'no_hp',
                    'category_id',
                    'sub_category_id',
                    'judul',
                    'deskripsi',
                    'anonymous',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket created successfully',
                'data' => [
                    'user_id' => $this->studentUser->id,
                    'nim' => $ticketData['nim'],
                    'prodi' => $ticketData['prodi'],
                    'status' => 'open'
                ]
            ]);
        
        // Assert that the ticket is in the database
        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->studentUser->id,
            'nim' => $ticketData['nim'],
            'judul' => $ticketData['judul'],
            'status' => 'open'
        ]);
        
        // Assert that ticket history was created
        $this->assertDatabaseHas('ticket_histories', [
            'action' => 'create',
            'new_status' => 'open',
            'updated_by' => $this->studentUser->id
        ]);
        
        // Assert that notifications were created for admins
        $this->assertDatabaseHas('notifications', [
            'recipient_role' => 'admin',
            'sender_id' => $this->studentUser->id,
            'type' => 'new_ticket'
        ]);
    }
    
    /**
     * Test getting list of tickets
     */
    public function test_getting_list_of_tickets(): void
    {
        // Create some test tickets
        $ticket1 = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open'
        ]);
        
        $ticket2 = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'in_progress',
            'assigned_to' => $this->disposisiUser->id
        ]);
        
        // Admin should see all tickets
        $adminResponse = $this->actingAs($this->adminUser)
            ->getJson('/api/tickets');
        
        $adminResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'tickets',
                    'pagination'
                ]
            ])
            ->assertJsonCount(2, 'data.tickets');
        
        // Student should only see their own tickets
        $studentResponse = $this->actingAs($this->studentUser)
            ->getJson('/api/tickets');
        
        $studentResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'tickets',
                    'pagination'
                ]
            ])
            ->assertJsonCount(2, 'data.tickets');
        
        // Disposisi should only see tickets assigned to them
        $disposisiResponse = $this->actingAs($this->disposisiUser)
            ->getJson('/api/tickets');
        
        $disposisiResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'tickets',
                    'pagination'
                ]
            ])
            ->assertJsonCount(1, 'data.tickets');
        
        // Test filtering by status
        $filteredResponse = $this->actingAs($this->adminUser)
            ->getJson('/api/tickets?status=open');
        
        $filteredResponse->assertStatus(200)
            ->assertJsonCount(1, 'data.tickets');
    }
    
    /**
     * Test getting ticket details
     */
    public function test_getting_ticket_detail(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open'
        ]);
        
        // Admin can access any ticket
        $adminResponse = $this->actingAs($this->adminUser)
            ->getJson("/api/tickets/{$ticket->id}");
        
        $adminResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'ticket' => [
                        'id',
                        'user_id',
                        'judul',
                        'status'
                    ]
                ]
            ]);
        
        // Student can access their own ticket
        $studentResponse = $this->actingAs($this->studentUser)
            ->getJson("/api/tickets/{$ticket->id}");
        
        $studentResponse->assertStatus(200);
        
        // Disposisi can't access ticket not assigned to them
        $disposisiResponse = $this->actingAs($this->disposisiUser)
            ->getJson("/api/tickets/{$ticket->id}");
        
        $disposisiResponse->assertStatus(403);
        
        // Now assign the ticket to disposisi user
        $ticket->assigned_to = $this->disposisiUser->id;
        $ticket->save();
        
        // Disposisi can now access the ticket
        $disposisiResponse2 = $this->actingAs($this->disposisiUser)
            ->getJson("/api/tickets/{$ticket->id}");
        
        $disposisiResponse2->assertStatus(200);
    }
    
    /**
     * Test updating ticket status
     */
    public function test_updating_ticket_status(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open'
        ]);
        
        // Admin can update any ticket status
        $adminResponse = $this->actingAs($this->adminUser)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'in_progress',
                'comment' => 'Status updated by admin'
            ]);
        
        $adminResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket status updated successfully',
                'data' => [
                    'status' => 'in_progress'
                ]
            ]);
        
        // Check database for updated status
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress'
        ]);
        
        // Check ticket history was created
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'status_change',
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'updated_by' => $this->adminUser->id
        ]);
        
        // Check feedback was created
        $this->assertDatabaseHas('ticket_feedbacks', [
            'ticket_id' => $ticket->id,
            'created_by' => $this->adminUser->id,
            'text' => 'Status updated by admin',
            'created_by_role' => 'admin'
        ]);
        
        // Disposisi can't update ticket not assigned to them
        $disposisiResponse = $this->actingAs($this->disposisiUser)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'resolved'
            ]);
        
        $disposisiResponse->assertStatus(403);
        
        // Assign ticket to disposisi user
        $ticket->assigned_to = $this->disposisiUser->id;
        $ticket->save();
        
        // Now disposisi can update the ticket
        $disposisiResponse2 = $this->actingAs($this->disposisiUser)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'resolved'
            ]);
        
        $disposisiResponse2->assertStatus(200);
        
        // Student can only close their ticket
        $studentResponse = $this->actingAs($this->studentUser)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'in_progress'
            ]);
        
        $studentResponse->assertStatus(403);
        
        $studentResponse2 = $this->actingAs($this->studentUser)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'closed'
            ]);
        
        $studentResponse2->assertStatus(200);
    }
    
    /**
     * Test assigning ticket to disposisi member
     */
    public function test_assigning_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open'
        ]);
        
        // Student cannot assign tickets
        $studentResponse = $this->actingAs($this->studentUser)
            ->postJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => $this->disposisiUser->id
            ]);
        
        $studentResponse->assertStatus(403);
        
        // Disposisi cannot assign tickets
        $disposisiResponse = $this->actingAs($this->disposisiUser)
            ->postJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => $this->disposisiUser->id
            ]);
        
        $disposisiResponse->assertStatus(403);
        
        // Admin can assign tickets
        $adminResponse = $this->actingAs($this->adminUser)
            ->postJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => $this->disposisiUser->id
            ]);
        
        $adminResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket assigned successfully',
                'data' => [
                    'assigned_to' => $this->disposisiUser->id
                ]
            ]);
        
        // Check database for assigned ticket
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $this->disposisiUser->id,
            'status' => 'in_progress'
        ]);
        
        // Check ticket history was created
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'assignment',
            'assigned_by' => $this->adminUser->id,
            'assigned_to' => $this->disposisiUser->id
        ]);
        
        // Check notification was created
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->disposisiUser->id,
            'recipient_role' => 'disposisi',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $ticket->id,
            'type' => 'assignment'
        ]);
    }
    
    /**
     * Test getting ticket statistics
     */
    public function test_getting_ticket_statistics(): void
    {
        // Create various tickets for testing
        $openTicket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'status' => 'open'
        ]);
        
        $inProgressTicket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'status' => 'in_progress',
            'assigned_to' => $this->disposisiUser->id
        ]);
        
        $resolvedTicket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'status' => 'resolved',
            'assigned_to' => $this->disposisiUser->id
        ]);
        
        // Admin can see all statistics
        $adminResponse = $this->actingAs($this->adminUser)
            ->getJson('/api/tickets/statistics');
        
        $adminResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total',
                    'new',
                    'in_progress',
                    'resolved',
                    'closed',
                    'unread',
                    'by_category'
                ]
            ])
            ->assertJson([
                'data' => [
                    'total' => 3,
                    'new' => 1,
                    'in_progress' => 1,
                    'resolved' => 1,
                    'closed' => 0
                ]
            ]);
        
        // Student can see only their own statistics
        $studentResponse = $this->actingAs($this->studentUser)
            ->getJson('/api/tickets/statistics');
        
        $studentResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total' => 3 // All tickets belong to this student
                ]
            ]);
        
        // Disposisi can see only their assigned tickets
        $disposisiResponse = $this->actingAs($this->disposisiUser)
            ->getJson('/api/tickets/statistics');
        
        $disposisiResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total' => 2,
                    'in_progress' => 1,
                    'resolved' => 1,
                    'closed' => 0
                ]
            ]);
    }
    
    /**
     * Test adding feedback to ticket
     */
    public function test_adding_feedback(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open'
        ]);
        
        // Admin can add feedback to any ticket
        $adminResponse = $this->actingAs($this->adminUser)
            ->postJson("/api/tickets/{$ticket->id}/feedback", [
                'text' => 'Feedback from admin'
            ]);
        
        $adminResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Feedback added successfully',
                'data' => [
                    'text' => 'Feedback from admin',
                    'created_by' => $this->adminUser->id,
                    'created_by_role' => 'admin'
                ]
            ]);
        
        // Check database for feedback
        $this->assertDatabaseHas('ticket_feedbacks', [
            'ticket_id' => $ticket->id,
            'created_by' => $this->adminUser->id,
            'text' => 'Feedback from admin',
            'created_by_role' => 'admin'
        ]);
        
        // Check notification was created for student
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->studentUser->id,
            'recipient_role' => 'student',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $ticket->id,
            'type' => 'feedback'
        ]);
        
        // Student can add feedback to their own ticket
        $studentResponse = $this->actingAs($this->studentUser)
            ->postJson("/api/tickets/{$ticket->id}/feedback", [
                'text' => 'Feedback from student'
            ]);
        
        $studentResponse->assertStatus(200);
        
        // Disposisi can't add feedback to unassigned ticket
        $disposisiResponse = $this->actingAs($this->disposisiUser)
            ->postJson("/api/tickets/{$ticket->id}/feedback", [
                'text' => 'Feedback from disposisi'
            ]);
        
        $disposisiResponse->assertStatus(403);
        
        // Assign ticket to disposisi
        $ticket->assigned_to = $this->disposisiUser->id;
        $ticket->save();
        
        // Now disposisi can add feedback
        $disposisiResponse2 = $this->actingAs($this->disposisiUser)
            ->postJson("/api/tickets/{$ticket->id}/feedback", [
                'text' => 'Feedback from disposisi'
            ]);
        
        $disposisiResponse2->assertStatus(200);
    }
    
    /**
     * Test soft deleting and restoring ticket
     */
    public function test_delete_and_restore_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->studentUser->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open'
        ]);
        
        // Student can delete their own ticket
        $studentResponse = $this->actingAs($this->studentUser)
            ->deleteJson("/api/tickets/{$ticket->id}");
        
        $studentResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket has been soft deleted'
            ]);
        
        // Check ticket is soft deleted
        $this->assertSoftDeleted('tickets', [
            'id' => $ticket->id
        ]);
        
        // Disposisi cannot restore ticket
        $disposisiResponse = $this->actingAs($this->disposisiUser)
            ->postJson("/api/tickets/{$ticket->id}/restore");
        
        $disposisiResponse->assertStatus(403);
        
        // Admin can restore ticket
        $adminResponse = $this->actingAs($this->adminUser)
            ->postJson("/api/tickets/{$ticket->id}/restore");
        
        $adminResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket has been restored'
            ]);
        
        // Check ticket is restored
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'deleted_at' => null
        ]);
        
        // Admin can delete any ticket
        $adminDeleteResponse = $this->actingAs($this->adminUser)
            ->deleteJson("/api/tickets/{$ticket->id}");
        
        $adminDeleteResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket has been soft deleted'
            ]);
        
        // Check ticket is soft deleted again
        $this->assertSoftDeleted('tickets', [
            'id' => $ticket->id
        ]);
    }
}
