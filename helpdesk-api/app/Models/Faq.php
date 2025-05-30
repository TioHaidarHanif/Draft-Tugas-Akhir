<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    // Pastikan factory namespace benar
    protected static function newFactory()
    {
        return \Database\Factories\FaqFactory::new();
    }

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'question',
        'answer',
        'category_id',
        'created_by',
        'ticket_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
