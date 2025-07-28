<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        // Create a category
        $this->category = Category::create([
            'name' => 'Test Category',
        ]);

        // Create a subcategory
        $this->subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Test SubCategory',
        ]);
    }

    public function test_can_create_ticket(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('tickets', [
            'judul' => 'Test Ticket',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_ticket_belongs_to_user(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open',
        ]);

        $this->assertEquals($this->user->id, $ticket->user->id);
        $this->assertEquals('Test User', $ticket->user->name);
    }

    public function test_ticket_belongs_to_category(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open',
        ]);

        $this->assertEquals($this->category->id, $ticket->category->id);
        $this->assertEquals('Test Category', $ticket->category->name);
    }

    public function test_ticket_belongs_to_subcategory(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open',
        ]);

        $this->assertEquals($this->subCategory->id, $ticket->subCategory->id);
        $this->assertEquals('Test SubCategory', $ticket->subCategory->name);
    }

    public function test_ticket_can_be_anonymous(): void
    {
        $ticket = Ticket::create([
            'anonymous' => true,
            'judul' => 'Anonymous Ticket',
            'deskripsi' => 'This is an anonymous ticket',
            'category_id' => $this->category->id,
            'status' => 'open',
            'nim' => '123456',
            'nama' => 'Anonymous Student',
            'email' => 'anon@example.com',
            'prodi' => 'Computer Science',
            'semester' => '5',
            'no_hp' => '123456789',
        ]);

        $this->assertTrue($ticket->anonymous);
        $this->assertEquals('Anonymous Student', $ticket->nama);
        $this->assertEquals('123456', $ticket->nim);
    }

    public function test_ticket_can_change_status(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open',
        ]);

        $ticket->status = 'in_progress';
        $ticket->save();

        $this->assertEquals('in_progress', $ticket->refresh()->status);

        $ticket->status = 'resolved';
        $ticket->save();

        $this->assertEquals('resolved', $ticket->refresh()->status);

        $ticket->status = 'closed';
        $ticket->save();

        $this->assertEquals('closed', $ticket->refresh()->status);
    }

    public function test_ticket_can_be_soft_deleted(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'status' => 'open',
        ]);

        $ticketId = $ticket->id;
        
        $ticket->delete();

        $this->assertSoftDeleted('tickets', [
            'id' => $ticketId,
        ]);
    }
}
