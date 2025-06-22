<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessageRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_message_id',
        'user_id',
        'read_at',
    ];

    public $timestamps = false;

    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
