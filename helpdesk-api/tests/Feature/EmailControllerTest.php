<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use App\Mail\ManualEmail;
use Tests\TestCase;

class EmailControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test sending an email as admin.
     */
    public function test_admin_can_send_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)
            ->postJson('/api/emails/send', [
                'email' => 'test@example.com',
                'subject' => 'Test Subject',
                'body' => 'Test Body Content'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email sent successfully'
            ]);

        Mail::assertSent(ManualEmail::class, function ($mail) {
            return $mail->subject === 'Test Subject' &&
                   $mail->hasTo('test@example.com');
        });

        $this->assertDatabaseHas('email_logs', [
            'user_id' => $admin->id,
            'to_email' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test Body Content',
            'status' => 'sent'
        ]);
    }

    /**
     * Test non-admin cannot send email.
     */
    public function test_non_admin_cannot_send_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'student']);
        
        $response = $this->actingAs($user)
            ->postJson('/api/emails/send', [
                'email' => 'test@example.com',
                'subject' => 'Test Subject',
                'body' => 'Test Body Content'
            ]);

        $response->assertStatus(403);
        
        Mail::assertNothingSent();
        
        $this->assertDatabaseMissing('email_logs', [
            'user_id' => $user->id,
            'to_email' => 'test@example.com'
        ]);
    }

    /**
     * Test validation when sending email.
     */
    public function test_email_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)
            ->postJson('/api/emails/send', [
                'email' => 'invalid-email',
                'subject' => '',
                'body' => ''
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'subject', 'body']);
    }

    /**
     * Test admin can view email logs.
     */
    public function test_admin_can_view_email_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        EmailLog::create([
            'user_id' => $admin->id,
            'to_email' => 'test@example.com',
            'subject' => 'Test Log',
            'content' => 'Test Log Content',
            'status' => 'sent'
        ]);
        
        $response = $this->actingAs($admin)
            ->getJson('/api/emails/logs');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email logs retrieved successfully'
            ])
            ->assertJsonStructure([
                'status', 
                'message', 
                'data' => [
                    'data' => [
                        '*' => ['id', 'user_id', 'to_email', 'subject', 'status']
                    ]
                ]
            ]);
    }

    /**
     * Test non-admin cannot view email logs.
     */
    public function test_non_admin_cannot_view_email_logs(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        
        $response = $this->actingAs($user)
            ->getJson('/api/emails/logs');

        $response->assertStatus(403);
    }
}
