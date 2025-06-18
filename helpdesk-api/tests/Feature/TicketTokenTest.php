<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TicketTokenTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a category and subcategory for testing
        $category = Category::create([
            'name' => 'Test Category',
            'description' => 'Test Category Description'
        ]);
        
        SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Test SubCategory',
            'description' => 'Test SubCategory Description'
        ]);
    }

    /**
     * Test token generation for anonymous tickets.
     */
    public function test_token_is_generated_for_anonymous_tickets(): void
    {
        // Create a student user
        $user = User::factory()->create(['role' => 'student']);
        
        // Act as the user
        $this->actingAs($user);
        
        // Create an anonymous ticket
        $response = $this->postJson('/api/tickets', [
            'anonymous' => true,
            'prodi' => 'Teknik Informatika',
            'semester' => '5',
            'no_hp' => '081234567890',
            'judul' => 'Test Anonymous Ticket',
            'category_id' => 1,
            'sub_category_id' => 1,
            'deskripsi' => 'This is a test anonymous ticket with token'
        ]);
        
        // Assert response
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'anonymous',
                        'judul',
                        'status'
                    ]
                ]);
        
        // Get the ticket from database
        $ticket = Ticket::find($response->json('data.id'));
        
        // Assert token was generated
        $this->assertNotNull($ticket->token);
        $this->assertTrue(strlen($ticket->token) > 0);
        
        // Assert token follows the correct format (XXXX-XXXX-XXXX)
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket->token);
    }
    
    /**
     * Test that non-anonymous tickets don't get tokens.
     */
    public function test_token_is_not_generated_for_non_anonymous_tickets(): void
    {
        // Create a student user
        $user = User::factory()->create(['role' => 'student']);
        
        // Act as the user
        $this->actingAs($user);
        
        // Create a non-anonymous ticket
        $response = $this->postJson('/api/tickets', [
            'anonymous' => false,
            'nim' => '12345678',
            'prodi' => 'Teknik Informatika',
            'semester' => '5',
            'no_hp' => '081234567890',
            'judul' => 'Test Regular Ticket',
            'category_id' => 1,
            'sub_category_id' => 1,
            'deskripsi' => 'This is a test regular ticket without token'
        ]);
        
        // Assert response
        $response->assertStatus(201);
        
        // Get the ticket from database
        $ticket = Ticket::find($response->json('data.id'));
        
        // Assert token was not generated
        $this->assertNull($ticket->token);
    }
    
    /**
     * Test that admins can see tokens without verification.
     */
    public function test_admin_can_see_token_without_verification(): void
    {
        // Create an admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a student user
        $student = User::factory()->create(['role' => 'student']);
        
        // Act as the student
        $this->actingAs($student);
        
        // Create an anonymous ticket
        $response = $this->postJson('/api/tickets', [
            'anonymous' => true,
            'prodi' => 'Teknik Informatika',
            'semester' => '5',
            'no_hp' => '081234567890',
            'judul' => 'Test Anonymous Ticket',
            'category_id' => 1,
            'sub_category_id' => 1,
            'deskripsi' => 'This is a test anonymous ticket with token'
        ]);
        
        $ticketId = $response->json('data.id');
        
        // Act as the admin
        $this->actingAs($admin);
        
        // Get ticket details
        $response = $this->getJson('/api/tickets/' . $ticketId);
        
        // Assert token is visible to admin
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'ticket' => [
                            'id',
                            'token',
                            'anonymous',
                            'judul'
                        ]
                    ]
                ]);
    }
    
    /**
     * Test that token can be revealed with correct password.
     */
    public function test_token_can_be_revealed_with_correct_password(): void
    {
        // Create a student user with known password
        $password = 'password123';
        $student = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt($password)
        ]);
        
        // Act as the student
        $this->actingAs($student);
        
        // Create an anonymous ticket
        $response = $this->postJson('/api/tickets', [
            'anonymous' => true,
            'prodi' => 'Teknik Informatika',
            'semester' => '5',
            'no_hp' => '081234567890',
            'judul' => 'Test Anonymous Ticket',
            'category_id' => 1,
            'sub_category_id' => 1,
            'deskripsi' => 'This is a test anonymous ticket with token'
        ]);
        
        $ticketId = $response->json('data.id');
        
        // Try to reveal token with correct password
        $response = $this->postJson('/api/tickets/' . $ticketId . '/reveal-token', [
            'password' => $password
        ]);
        
        // Assert token is revealed
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'token'
                    ]
                ]);
    }
    
    /**
     * Test that token revelation fails with incorrect password.
     */
    public function test_token_revelation_fails_with_incorrect_password(): void
    {
        // Create a student user with known password
        $student = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('correct_password')
        ]);
        
        // Act as the student
        $this->actingAs($student);
        
        // Create an anonymous ticket
        $response = $this->postJson('/api/tickets', [
            'anonymous' => true,
            'prodi' => 'Teknik Informatika',
            'semester' => '5',
            'no_hp' => '081234567890',
            'judul' => 'Test Anonymous Ticket',
            'category_id' => 1,
            'sub_category_id' => 1,
            'deskripsi' => 'This is a test anonymous ticket with token'
        ]);
        
        $ticketId = $response->json('data.id');
        
        // Try to reveal token with incorrect password
        $response = $this->postJson('/api/tickets/' . $ticketId . '/reveal-token', [
            'password' => 'wrong_password'
        ]);
        
        // Assert token revelation failed
        $response->assertStatus(401);
    }
    
    /**
     * Test that token service generates valid tokens
     */
    public function test_token_service_generates_valid_tokens(): void
    {
        $tokenService = new TokenService();
        
        // Generate a token
        $token = $tokenService->generateToken();
        
        // Assert token format
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $token);
        
        // Assert token is validated correctly
        $this->assertTrue($tokenService->validateTokenFormat($token));
        $this->assertFalse($tokenService->validateTokenFormat('invalid-token'));
    }
}
