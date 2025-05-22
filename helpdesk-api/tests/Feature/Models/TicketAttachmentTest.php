<?php

namespace Tests\Feature\Models;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
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

    public function test_can_create_ticket_attachment(): void
    {
        $attachment = TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'file_name' => 'test_file.pdf',
            'file_type' => 'application/pdf',
            'file_url' => 'storage/attachments/test_file.pdf',
            'file_base64' => 'base64encodedstring',
        ]);

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $this->ticket->id,
            'file_name' => 'test_file.pdf',
            'file_type' => 'application/pdf',
        ]);
    }

    public function test_ticket_attachment_belongs_to_ticket(): void
    {
        $attachment = TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'file_name' => 'test_file.pdf',
            'file_type' => 'application/pdf',
            'file_url' => 'storage/attachments/test_file.pdf',
            'file_base64' => 'base64encodedstring',
        ]);

        $this->assertEquals($this->ticket->id, $attachment->ticket->id);
        $this->assertEquals('Test Ticket', $attachment->ticket->judul);
    }

    public function test_can_create_multiple_attachments_per_ticket(): void
    {
        // Create first attachment
        TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'file_name' => 'first_file.pdf',
            'file_type' => 'application/pdf',
            'file_url' => 'storage/attachments/first_file.pdf',
            'file_base64' => 'base64encodedstring1',
        ]);

        // Create second attachment
        TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'file_name' => 'second_file.jpg',
            'file_type' => 'image/jpeg',
            'file_url' => 'storage/attachments/second_file.jpg',
            'file_base64' => 'base64encodedstring2',
        ]);

        // Check that both attachments are in the database
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $this->ticket->id,
            'file_name' => 'first_file.pdf',
        ]);

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $this->ticket->id,
            'file_name' => 'second_file.jpg',
        ]);

        // Check that the ticket has two attachments
        $this->assertEquals(2, $this->ticket->attachments->count());
    }
}
