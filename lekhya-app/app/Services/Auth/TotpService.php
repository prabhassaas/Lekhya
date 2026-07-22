<?php

namespace App\Services\Auth;

/**
 * Self-contained TOTP (RFC 6238) — compatible with Google Authenticator, Authy,
 * Microsoft Authenticator. Pure PHP so it adds no Composer dependency.
 */
class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // base32
    private const PERIOD = 30;
    private const DIGITS = 6;

    /** A fresh base32 secret (default 160 bits, the RFC-recommended length). */
    public function generateSecret(int $bytes = 20): string
    {
        $random = random_bytes($bytes);
        $secret = '';
        $buffer = 0;
        $bitsLeft = 0;
        foreach (str_split($random) as $char) {
            $buffer = ($buffer << 8) | ord($char);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $secret .= self::ALPHABET[($buffer >> $bitsLeft) & 0x1F];
            }
        }
        if ($bitsLeft > 0) {
            $secret .= self::ALPHABET[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $secret;
    }

    /** The 6-digit code for a secret at a given time step. */
    public function codeAt(string $secret, ?int $timestamp = null, int $offset = 0): string
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, self::PERIOD) + $offset;

        $binCounter = pack('N*', 0) . pack('N*', $counter); // 64-bit big-endian
        $hash = hash_hmac('sha1', $binCounter, $this->base32Decode($secret), true);

        $o = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$o]) & 0x7F) << 24)
            | ((ord($hash[$o + 1]) & 0xFF) << 16)
            | ((ord($hash[$o + 2]) & 0xFF) << 8)
            | (ord($hash[$o + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Verify a user-entered code, tolerating ±$window steps for clock drift. */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->codeAt($secret, null, $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /** otpauth:// URI to embed in the enrolment QR code. */
    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);
        $query = http_build_query([
            'secret'  => $secret,
            'issuer'  => $issuer,
            'digits'  => self::DIGITS,
            'period'  => self::PERIOD,
            'algorithm' => 'SHA1',
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    private function base32Decode(string $secret): string
    {
        $secret = rtrim(strtoupper($secret), '=');
        if ($secret === '') {
            return '';
        }
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                continue; // ignore stray formatting
            }
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
