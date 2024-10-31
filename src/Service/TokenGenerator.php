<?php

namespace App\Service;

class TokenGenerator
{
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32)); // Generates a secure random token
    }
}
