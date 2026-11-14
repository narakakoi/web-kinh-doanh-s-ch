<?php

function verify_account_password(string $password, string $stored): bool
{
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        // Old accounts used MD5 after the string filter. Resolve its ID without
        // referencing the deprecated constant; keep this only for migration.
        $legacyPassword = filter_var($password, filter_id('string'));
        return hash_equals(strtolower($stored), md5($password))
            || hash_equals(strtolower($stored), md5((string) $legacyPassword));
    }
    if (str_contains($password, "\0") || (str_starts_with($stored, '$2y$') && strlen($password) > 72)) {
        return false;
    }
    return password_verify($password, $stored);
}
