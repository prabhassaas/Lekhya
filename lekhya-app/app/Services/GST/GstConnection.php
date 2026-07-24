<?php

namespace App\Services\GST;

use App\Models\GstFiling;
use App\Models\GstSetting;
use App\Models\Tenant;

/**
 * Resolves the GST connection for a tenant: are they entitled (plan), connected
 * (own GSP credentials), and within quota — and meters every transactional call.
 * This is what makes GSP go multi-tenant safely: each company transacts under
 * its own GSTIN, never a shared identity.
 */
class GstConnection
{
    public function __construct(private ?Tenant $tenant = null) {}

    public function forTenant(Tenant $tenant): self
    {
        return new self($tenant);
    }

    private function tenant(): ?Tenant
    {
        return $this->tenant ?? auth()->user()?->tenant;
    }

    public function setting(): GstSetting
    {
        return GstSetting::firstOrNew(['tenant_id' => $this->tenant()?->id]);
    }

    /** Plan includes GST filing. */
    public function isEntitled(): bool
    {
        return (bool) $this->tenant()?->gstFilingEnabled();
    }

    /** This company has plugged in its own GSTIN + credentials. */
    public function isConnected(): bool
    {
        return (bool) $this->tenant()?->gstConnected();
    }

    /** Monthly filing allowance spent. */
    public function limitReached(): bool
    {
        return (bool) $this->tenant()?->gstFilingsExhausted();
    }

    /** All three must hold before any real GST call is made. */
    public function canFile(): bool
    {
        return $this->isEntitled() && $this->isConnected() && ! $this->limitReached();
    }

    /** Why a call is blocked (for a helpful message), or null if it may proceed. */
    public function blockReason(): ?string
    {
        if (! $this->isEntitled()) {
            return 'not_entitled';
        }
        if (! $this->isConnected()) {
            return 'not_connected';
        }
        if ($this->limitReached()) {
            return 'limit_reached';
        }
        return null;
    }

    /**
     * The gateway to use for THIS tenant. The central binding already handles
     * GSTIN verification (Cashfree) and the mock GSP. When a real GSP client is
     * wired, build it here from the tenant's own stored credentials, e.g.:
     *   $c = $this->setting()->credentials('einvoice');
     *   return new GspGateway($c, config('services.gst'), $this->setting()->environment);
     */
    public function gateway(): GstGateway
    {
        return app(GstGateway::class);
    }

    /** Record one transactional GST call for billing / audit. */
    public function meter(string $type, ?string $reference = null, string $status = 'success', array $meta = []): GstFiling
    {
        $tenant  = $this->tenant();
        $setting = $tenant?->gstSetting;

        return GstFiling::create([
            'tenant_id'   => $tenant?->id,
            'user_id'     => auth()->id(),
            'type'        => $type,
            'gstin'       => $setting?->gstin ?? $tenant?->gstin,
            'reference'   => $reference,
            'status'      => $status,
            'environment' => $setting?->environment ?? 'sandbox',
            'billable'    => $status !== 'failed',
            'meta'        => $meta ?: null,
        ]);
    }
}
