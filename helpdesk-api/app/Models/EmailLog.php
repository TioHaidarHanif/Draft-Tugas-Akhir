<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailLog extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'to_email',
        'subject',
        'content',
        'status',
        'error_message',
    ];

    /**
     * Get the user that sent the email.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
