<?php

namespace Tests\Unit;

use App\Services\Auth\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    // RFC 6238 shared secret "12345678901234567890" (ASCII) in base32.
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService();
    }

    /** The canonical RFC 6238 SHA-1 vectors — proves base32 + HMAC + truncation. */
    public function test_matches_rfc6238_vectors(): void
    {
        $cases = [
            59          => '287082',
            1111111109  => '081804',
            1111111111  => '050471',
            1234567890  => '005924',
            2000000000  => '279037',
        ];
        foreach ($cases as $time => $expected) {
            $this->assertSame($expected, $this->totp->codeAt(self::RFC_SECRET, $time), "vector at t={$time}");
        }
    }

    public function test_verify_accepts_current_code_and_rejects_wrong(): void
    {
        $secret = $this->totp->generateSecret();
        $code = $this->totp->codeAt($secret);

        $this->assertTrue($this->totp->verify($secret, $code));
        $this->assertFalse($this->totp->verify($secret, '000000'));
        $this->assertFalse($this->totp->verify($secret, 'abc'));
    }

    public function test_verify_tolerates_one_step_drift(): void
    {
        $secret = $this->totp->generateSecret();
        // A code from the previous 30s window should still pass (window = 1).
        $prev = $this->totp->codeAt($secret, time() - 30);
        $this->assertTrue($this->totp->verify($secret, $prev, 1));
    }

    public function test_generated_secret_is_valid_base32(): void
    {
        $secret = $this->totp->generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertGreaterThanOrEqual(32, strlen($secret));
    }

    public function test_provisioning_uri_is_well_formed(): void
    {
        $uri = $this->totp->provisioningUri('ABC234', 'user@acme.co', 'Lekhya');
        $this->assertStringStartsWith('otpauth://totp/Lekhya:user%40acme.co?', $uri);
        $this->assertStringContainsString('secret=ABC234', $uri);
        $this->assertStringContainsString('issuer=Lekhya', $uri);
    }
}
