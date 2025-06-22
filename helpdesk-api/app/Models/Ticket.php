<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'anonymous',
        'judul',
        'deskripsi',
        'category_id',
        'sub_category_id',
        'status',
        'assigned_to',
        'nim',
        'nama',
        'email',
        'prodi',
        'semester',
        'no_hp',
        'read_by_admin',
        'read_by_disposisi',
        'read_by_student',
        'token', // tambahkan token agar mass assignable
        'prioritas', // tambahkan prioritas agar mass assignable
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'anonymous' => 'boolean',
        'read_by_admin' => 'boolean',
        'read_by_disposisi' => 'boolean',
        'read_by_student' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that created the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the ticket.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the sub-category of the ticket.
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Get the user that the ticket is assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the attachments for the ticket.
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get the attachment for the ticket (first attachment only).
     */
    public function attachment()
    {
        return $this->hasOne(TicketAttachment::class)->latestOfMany();
    }

    /**
     * Get the histories for the ticket.
     */
    public function histories()
    {
        return $this->hasMany(TicketHistory::class);
    }

    /**
     * Get the feedbacks for the ticket.
     */
    public function feedbacks()
    {
        return $this->hasMany(TicketFeedback::class);
    }

    /**
     * Get the notifications related to the ticket.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the chat messages for the ticket.
     */
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'ticket_id', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($ticket) {
            if ($ticket->anonymous && empty($ticket->token)) {
                // Gunakan helper untuk generate token
                $ticket->token = app(\App\Services\TicketTokenService::class)->generateToken();
            }
        });
    }

    /**
     * Validasi token (opsional, bisa dikembangkan lebih lanjut)
     */
    public function validateToken($token)
    {
        return hash_equals($this->token, $token);
    }

    public function getChatCountAttribute()
    {
        // Gunakan eager loading withCount('chatMessages') jika memungkinkan
        if (array_key_exists('chat_messages_count', $this->getAttributes())) {
            return $this->getAttribute('chat_messages_count');
        }
        return $this->chatMessages()->count();
    }

    public function getHasUnreadChatAttribute()
    {
        $user = auth()->user();
        if (!$user) return false;
        // Asumsi ada tabel chat_reads atau field is_read pada ChatMessage, jika tidak, perlu dibuat
        // Di sini diasumsikan ada relasi unread untuk user pada ChatMessage
        return $this->chatMessages()
            ->whereDoesntHave('reads', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('user_id', '!=', $user->id) // exclude own messages
            ->exists();
    }
}
