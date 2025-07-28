<?php

namespace Tests\Feature\Models;

use App\Models\Ticket;
use App\Models\TicketFeedback;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFeedbackTest extends TestCase
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
            'role' => 'student',
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

    public function test_can_create_ticket_feedback(): void
    {
        $feedback = TicketFeedback::create([
            'ticket_id' => $this->ticket->id,
            'created_by' => $this->user->id,
            'text' => 'This is some feedback on the ticket.',
            'created_by_role' => 'user',
        ]);

        $this->assertDatabaseHas('ticket_feedbacks', [
            'ticket_id' => $this->ticket->id,
            'created_by' => $this->user->id,
            'text' => 'This is some feedback on the ticket.',
            'created_by_role' => 'user',
        ]);
    }

    public function test_ticket_feedback_belongs_to_ticket(): void
    {
        $feedback = TicketFeedback::create([
            'ticket_id' => $this->ticket->id,
            'created_by' => $this->user->id,
            'text' => 'This is some feedback on the ticket.',
            'created_by_role' => 'user',
        ]);

        $this->assertEquals($this->ticket->id, $feedback->ticket->id);
        $this->assertEquals('Test Ticket', $feedback->ticket->judul);
    }

    public function test_ticket_feedback_belongs_to_creator(): void
    {
        $feedback = TicketFeedback::create([
            'ticket_id' => $this->ticket->id,
            'created_by' => $this->user->id,
            'text' => 'This is some feedback on the ticket.',
            'created_by_role' => 'user',
        ]);

        $this->assertEquals($this->user->id, $feedback->creator->id);
        $this->assertEquals('Test User', $feedback->creator->name);
    }

    public function test_different_roles_can_create_feedback(): void
    {
        // User feedback
        $userFeedback = TicketFeedback::create([
            'ticket_id' => $this->ticket->id,
            'created_by' => $this->user->id,
            'text' => 'User feedback',
            'created_by_role' => 'user',
        ]);

        // Admin feedback
        $adminFeedback = TicketFeedback::create([
            'ticket_id' => $this->ticket->id,
            'created_by' => $this->adminUser->id,
            'text' => 'Admin feedback',
            'created_by_role' => 'admin',
        ]);

        // disposisi feedback
        $disposisiFeedback = TicketFeedback::create([
            'ticket_id' => $this->ticket->id,
            'created_by' => $this->disposisiUser->id,
            'text' => 'disposisi feedback',
            'created_by_role' => 'disposisi',
        ]);

        // Check all feedbacks are in database
        $this->assertDatabaseHas('ticket_feedbacks', [
            'created_by' => $this->user->id,
            'text' => 'User feedback',
            'created_by_role' => 'user',
        ]);

        $this->assertDatabaseHas('ticket_feedbacks', [
            'created_by' => $this->adminUser->id,
            'text' => 'Admin feedback',
            'created_by_role' => 'admin',
        ]);

        $this->assertDatabaseHas('ticket_feedbacks', [
            'created_by' => $this->disposisiUser->id,
            'text' => 'disposisi feedback',
            'created_by_role' => 'disposisi',
        ]);

        // Check the ticket has 3 feedbacks
        $this->assertEquals(3, $this->ticket->feedbacks()->count());
    }
}
