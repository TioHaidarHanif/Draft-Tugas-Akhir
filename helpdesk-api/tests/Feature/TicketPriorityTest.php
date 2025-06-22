<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TicketPriorityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private User $disposisi;
    private Category $category;
    private SubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->disposisi = User::factory()->create(['role' => 'disposisi']);
        
        // Create category and subcategory
        $this->category = Category::factory()->create();
        $this->subCategory = SubCategory::factory()->create(['category_id' => $this->category->id]);
    }

    /**
     * Test creating a ticket with a priority value.
     */
    public function testCreateTicketWithPriority(): void
    {
        // Login as student
        $this->actingAs($this->student);
        
        // Create ticket with high priority
        $response = $this->postJson('/api/tickets', [
            'judul' => 'Test Ticket with Priority',
            'deskripsi' => 'This is a test ticket with high priority',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'prodi' => 'Informatika',
            'semester' => '3',
            'no_hp' => '081234567890',
            'priority' => 'high'
        ]);
        
        // Assert success and check priority value
        $response->assertStatus(201)
                 ->assertJsonPath('data.priority', 'high');
                 
        // Verify in database
        $this->assertDatabaseHas('tickets', [
            'judul' => 'Test Ticket with Priority',
            'priority' => 'high'
        ]);
    }
    
    /**
     * Test creating a ticket without priority (should default to medium).
     */
    public function testCreateTicketWithoutPriority(): void
    {
        // Login as student
        $this->actingAs($this->student);
        
        // Create ticket without priority
        $response = $this->postJson('/api/tickets', [
            'judul' => 'Test Ticket without Priority',
            'deskripsi' => 'This is a test ticket without priority specified',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'prodi' => 'Informatika',
            'semester' => '3',
            'no_hp' => '081234567890'
        ]);
        
        // Assert success and check default priority is medium
        $response->assertStatus(201)
                 ->assertJsonPath('data.priority', 'medium');
                 
        // Verify in database
        $this->assertDatabaseHas('tickets', [
            'judul' => 'Test Ticket without Priority',
            'priority' => 'medium'
        ]);
    }
    
    /**
     * Test updating a ticket's priority.
     */
    public function testUpdateTicketPriority(): void
    {
        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $this->student->id,
            'priority' => 'low',
            'assigned_to' => $this->disposisi->id
        ]);
        
        // Login as admin
        $this->actingAs($this->admin);
        
        // Update priority to urgent
        $response = $this->patchJson("/api/tickets/{$ticket->id}/priority", [
            'priority' => 'urgent',
            'comment' => 'This needs immediate attention'
        ]);
        
        // Assert success and check updated priority
        $response->assertStatus(200)
                 ->assertJsonPath('data.priority', 'urgent');
                 
        // Verify in database
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'urgent'
        ]);
        
        // Verify history was created
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'priority_change',
            'old_priority' => 'low',
            'new_priority' => 'urgent'
        ]);
    }
    
    /**
     * Test validation for invalid priority values.
     */
    public function testInvalidPriorityValidation(): void
    {
        // Login as student
        $this->actingAs($this->student);
        
        // Try to create ticket with invalid priority
        $response = $this->postJson('/api/tickets', [
            'judul' => 'Test Ticket with Invalid Priority',
            'deskripsi' => 'This is a test ticket with invalid priority',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'prodi' => 'Informatika',
            'semester' => '3',
            'no_hp' => '081234567890',
            'priority' => 'critical' // Invalid value
        ]);
        
        // Assert validation error
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['priority']);
    }
    
    /**
     * Test that disposisi can update ticket priority.
     */
    public function testDisposisiCanUpdatePriority(): void
    {
        // Create a ticket assigned to disposisi
        $ticket = Ticket::factory()->create([
            'user_id' => $this->student->id,
            'priority' => 'medium',
            'assigned_to' => $this->disposisi->id
        ]);
        
        // Login as disposisi
        $this->actingAs($this->disposisi);
        
        // Update priority to high
        $response = $this->patchJson("/api/tickets/{$ticket->id}/priority", [
            'priority' => 'high'
        ]);
        
        // Assert success
        $response->assertStatus(200)
                 ->assertJsonPath('data.priority', 'high');
    }
    
    /**
     * Test that student cannot update ticket priority.
     */
    public function testStudentCannotUpdatePriority(): void
    {
        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $this->student->id,
            'priority' => 'medium'
        ]);
        
        // Login as student (owner of the ticket)
        $this->actingAs($this->student);
        
        // Try to update priority
        $response = $this->patchJson("/api/tickets/{$ticket->id}/priority", [
            'priority' => 'high'
        ]);
        
        // Assert forbidden
        $response->assertStatus(403);
    }
}
