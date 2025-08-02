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
            'nama'=> 'John Doe',
            'nim' => '1234567890',
            'prodi' => 'Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
            'judul' => 'Test Ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'deskripsi' => 'This is a test ticket description',
            'anonymous' => 'false',
            
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
                  
                    'status' => 'open'
                ]
            ]);
        
        // Assert that the ticket is in the database
        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->studentUser->id,
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
        
      
        
        // Now assign the ticket to disposisi user
        $ticket->assigned_to = $this->disposisiUser->id;
        $ticket->save();
        
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
        $studentResponse->assertStatus(200);


       
    }
}
