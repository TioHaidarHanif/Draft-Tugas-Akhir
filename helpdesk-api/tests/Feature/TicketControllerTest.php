<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_ticket()
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth_token')->plainTextToken;
        $category = \App\Models\Category::factory()->create();
        $subCategory = \App\Models\SubCategory::factory()->create(['category_id' => $category->id]);
        $payload = [
            'judul' => 'Test Ticket',
            'deskripsi' => 'Deskripsi keluhan',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'prodi' => 'Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
            'anonymous' => false,
            'lampiran' => UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'),
        ];
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tickets', $payload);
        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);
        Storage::disk('public')->assertExists('lampiran/' . $payload['lampiran']->hashName());
    }

    public function test_student_can_list_own_tickets()
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth_token')->plainTextToken;
        Ticket::factory()->count(2)->create(['user_id' => $user->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tickets');
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_admin_can_assign_ticket()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $disposisi = User::factory()->create(['role' => 'disposisi']);
        $ticket = Ticket::factory()->create();
        $token = $admin->createToken('auth_token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assigned_to' => $disposisi->id]);
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_disposisi_can_update_ticket_status()
    {
        $disposisi = User::factory()->create(['role' => 'disposisi']);
        $ticket = Ticket::factory()->create(['assigned_to' => $disposisi->id]);
        $token = $disposisi->createToken('auth_token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => 'resolved']);
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_student_can_soft_delete_and_restore_ticket()
    {
        $user = User::factory()->create(['role' => 'student']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('auth_token')->plainTextToken;
        $del = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/tickets/{$ticket->id}");
        $del->assertStatus(200)->assertJson(['status' => 'success']);
        $restore = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/tickets/{$ticket->id}/restore");
        $restore->assertStatus(200)->assertJson(['status' => 'success']);
    }

    public function test_student_can_view_ticket_detail()
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth_token')->plainTextToken;
        $category = \App\Models\Category::factory()->create();
        $subCategory = \App\Models\SubCategory::factory()->create(['category_id' => $category->id]);
        $ticket = \App\Models\Ticket::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
        ]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/tickets/{$ticket->id}");
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_admin_can_view_ticket_statistics()
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;
        \App\Models\Ticket::factory()->count(2)->create();
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tickets/statistics');
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_student_can_add_feedback_to_ticket()
    {
        $user = \App\Models\User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth_token')->plainTextToken;
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/tickets/{$ticket->id}/feedback", ['text' => 'Feedback test']);
        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);
    }

    public function test_forbidden_delete_restore_ticket_of_other_user()
    {
        $user1 = \App\Models\User::factory()->create(['role' => 'student']);
        $user2 = \App\Models\User::factory()->create(['role' => 'student']);
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user2->id]);
        $token = $user1->createToken('auth_token')->plainTextToken;
        $del = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/tickets/{$ticket->id}");
        $del->assertStatus(403);
        $ticket->delete();
        $restore = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/tickets/{$ticket->id}/restore");
        $restore->assertStatus(403);
    }
}
