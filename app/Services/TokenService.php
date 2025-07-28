<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Ticket;

class TokenService
{
    /**
     * Character set used for generating readable tokens
     * Excludes similar looking characters like O/0, l/I/1
     *
     * @var string
     */
    protected $characterSet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    
    /**
     * Generate a secure, user-friendly token for anonymous tickets
     * Format: XXXX-XXXX-XXXX (12 characters with dashes)
     *
     * @return string
     */
    public function generateToken(): string
    {
        $token = '';
        
        // Generate 4 chunks of 3 characters each
        for ($i = 0; $i < 3; $i++) {
            $chunk = '';
            for ($j = 0; $j < 4; $j++) {
                $randomIndex = random_int(0, strlen($this->characterSet) - 1);
                $chunk .= $this->characterSet[$randomIndex];
            }
            $token .= $chunk;
            
            // Add dash except after the last chunk
            if ($i < 2) {
                $token .= '-';
            }
        }
        
        // Ensure token is unique
        if ($this->tokenExists($token)) {
            return $this->generateToken(); // Recursively try again
        }
        
        return $token;
    }
    
    /**
     * Check if a token already exists in the database
     *
     * @param string $token
     * @return bool
     */
    public function tokenExists(string $token): bool
    {
        return Ticket::where('token', $token)->exists();
    }
    
    /**
     * Validate if a token has the correct format
     *
     * @param string $token
     * @return bool
     */
    public function validateTokenFormat(string $token): bool
    {
        // Check if token matches the pattern: XXXX-XXXX-XXXX
        return preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $token) === 1;
    }
}
