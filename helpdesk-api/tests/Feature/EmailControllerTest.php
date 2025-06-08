<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ManualMail;
use Tests\TestCase;

class EmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function only_admin_can_send_email()
    {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();
        $payload = [
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
        ];

        // Admin can send
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/emails/send', $payload)
            ->assertStatus(200);

        // User cannot send
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/emails/send', $payload)
            ->assertStatus(403);
    }

    /** @test */
    public function email_send_requires_valid_input()
    {
        $admin = User::factory()->asAdmin()->create();
        $payload = [
            'email' => 'not-an-email',
            'subject' => '',
            'body' => '',
        ];
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/emails/send', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'subject', 'body']);
    }

    /** @test */
    public function only_admin_can_view_email_logs()
    {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();
        EmailLog::factory()->count(2)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/emails/logs')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/emails/logs')
            ->assertStatus(403);
    }
}
