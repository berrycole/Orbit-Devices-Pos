<?php

namespace App\Validation;

class PasswordRules
{
    public function passwordBytes($value, ?string &$error = null): bool
    {
        // Bcrypt uses at most 72 bytes, which may be fewer than 72 Unicode characters.
        if (! is_string($value) || strlen($value) > 72 || str_contains($value, "\0")) {
            $error = 'The password must be at most 72 bytes and cannot contain null characters.';

            return false;
        }

        return true;
    }
}
