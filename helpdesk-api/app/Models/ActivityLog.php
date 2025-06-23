<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'activity',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
