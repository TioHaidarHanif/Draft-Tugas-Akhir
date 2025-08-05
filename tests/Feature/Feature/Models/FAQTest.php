<?php

namespace Tests\Feature\Feature\Models;

use App\Models\Category;
use App\Models\FAQ;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FAQTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_faq()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create(['role' => 'admin']);
        
        $faqData = [
            'question' => 'How do I reset my password?',
            'answer' => 'You can reset your password by clicking on the "Forgot Password" link on the login page.',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'is_public' => true,
        ];
        
        $faq = FAQ::create($faqData);
        
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'How do I reset my password?',
        ]);
        
        $this->assertEquals('How do I reset my password?', $faq->question);
        $this->assertEquals('You can reset your password by clicking on the "Forgot Password" link on the login page.', $faq->answer);
        $this->assertEquals($category->id, $faq->category_id);
        $this->assertEquals($user->id, $faq->user_id);
        $this->assertTrue($faq->is_public);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_soft_deleted()
    {
        $faq = FAQ::factory()->create();
        
        $faq->delete();
        
        $this->assertSoftDeleted('faqs', ['id' => $faq->id]);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_belongs_to_a_category()
    {
        $category = Category::factory()->create();
        $faq = FAQ::factory()->create(['category_id' => $category->id]);
        
        $this->assertInstanceOf(Category::class, $faq->category);
        $this->assertEquals($category->id, $faq->category->id);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $faq = FAQ::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $faq->user);
        $this->assertEquals($user->id, $faq->user->id);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_created_from_a_ticket()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['category_id' => $category->id]);
        
        $faq = FAQ::create([
            'question' => 'How do I submit a ticket?',
            'answer' => 'You can submit a ticket by clicking on the "Submit Ticket" button on the dashboard.',
            'category_id' => $ticket->category_id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'is_public' => true,
        ]);
        
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'ticket_id' => $ticket->id,
        ]);
        
        $this->assertInstanceOf(Ticket::class, $faq->ticket);
        $this->assertEquals($ticket->id, $faq->ticket->id);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_increment_view_count()
    {
        $faq = FAQ::factory()->create(['view_count' => 0]);
        
        $faq->incrementViewCount();
        
        $this->assertEquals(1, $faq->fresh()->view_count);
        
        $faq->incrementViewCount();
        $faq->incrementViewCount();
        
        $this->assertEquals(3, $faq->fresh()->view_count);
    }
}
