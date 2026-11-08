<?php

namespace App\Services\Auth;

use Fouladgar\OTP\Token\DatabaseTokenRepository;

class HashedDatabaseTokenRepository extends DatabaseTokenRepository
{
    /**
     * Compute HMAC-SHA256 hash of token to prevent plaintext storage at rest.
     */
    protected function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    /**
     * Store the hashed token in the database.
     */
    protected function save(string $recipient, string $purpose, string $token): bool
    {
        return parent::save($recipient, $purpose, $this->hashToken($token));
    }

    /**
     * Match token against hashed database record.
     */
    public function isTokenMatching(string $recipient, string $purpose, string $token): bool
    {
        return parent::isTokenMatching($recipient, $purpose, $this->hashToken($token));
    }
}
