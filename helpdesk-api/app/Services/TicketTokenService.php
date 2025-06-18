<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Str;

class TicketTokenService
{
    /**
     * Generate a unique, user-friendly token for anonymous ticket
     * @return string
     */
    public function generateToken($length = 8)
    {
        do {
            // Kombinasi huruf kapital dan angka, mudah diingat
            $token = Str::upper(Str::random($length));
            // Bisa juga gunakan custom charset jika ingin lebih readable
        } while (Ticket::where('token', $token)->exists());
        return $token;
    }
}
