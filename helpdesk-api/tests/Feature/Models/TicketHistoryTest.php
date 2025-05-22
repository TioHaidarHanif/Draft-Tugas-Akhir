<?php

namespace Tests\Feature\Models;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;
    protected $disposisiUser;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->disposisiUser = User::create([
            'name' => 'disposisi User',
            'email' => 'disposisi@example.com',
            'password' => bcrypt('password'),
            'role' => 'disposisi',
        ]);

        // Create a category
        $category = Category::create([
            'name' => 'Test Category',
        ]);

        // Create a subcategory
        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Test SubCategory',
        ]);

        // Create a ticket
        $this->ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => 'open',
        ]);
    }

    public function test_can_create_ticket_history(): void
    {
        $history = TicketHistory::create([
            'ticket_id' => $this->ticket->id,
            'action' => 'status_change',
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'updated_by' => $this->adminUser->id,
            'timestamp' => now(),
        ]);

        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $this->ticket->id,
            'action' => 'status_change',
            'old_status' => 'open',
            'new_status' => 'in_progress',
        ]);
    }

    public function test_ticket_history_belongs_to_ticket(): void
    {
        $history = TicketHistory::create([
            'ticket_id' => $this->ticket->id,
            'action' => 'status_change',
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'updated_by' => $this->adminUser->id,
            'timestamp' => now(),
        ]);

        $this->assertEquals($this->ticket->id, $history->ticket->id);
        $this->assertEquals('Test Ticket', $history->ticket->judul);
    }

    public function test_can_track_assignment_history(): void
    {
        $history = TicketHistory::create([
            'ticket_id' => $this->ticket->id,
            'action' => 'assignment',
            'assigned_by' => $this->adminUser->id,
            'assigned_to' => $this->disposisiUser->id,
            'timestamp' => now(),
        ]);

        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $this->ticket->id,
            'action' => 'assignment',
            'assigned_by' => $this->adminUser->id,
            'assigned_to' => $this->disposisiUser->id,
        ]);
    }

    public function test_can_track_multiple_history_events(): void
    {
        // First history entry - status change
        TicketHistory::create([
            'ticket_id' => $this->ticket->id,
            'action' => 'status_change',
            'old_status' => 'open',
            'new_status' => 'in_progress',
            'updated_by' => $this->adminUser->id,
            'timestamp' => now()->subHours(2),
        ]);

        // Second history entry - assignment
        TicketHistory::create([
            'ticket_id' => $this->ticket->id,
            'action' => 'assignment',
            'assigned_by' => $this->adminUser->id,
            'assigned_to' => $this->disposisiUser->id,
            'timestamp' => now()->subHour(),
        ]);

        // Third history entry - another status change
        TicketHistory::create([
            'ticket_id' => $this->ticket->id,
            'action' => 'status_change',
            'old_status' => 'in_progress',
            'new_status' => 'resolved',
            'updated_by' => $this->disposisiUser->id,
            'timestamp' => now(),
        ]);

        // Check all history entries are in database
        $this->assertDatabaseCount('ticket_histories', 3);
        
        // Check the ticket has 3 history entries
        $this->assertEquals(3, $this->ticket->histories()->count());
    }
}
