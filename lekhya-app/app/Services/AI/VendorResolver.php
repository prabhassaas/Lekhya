<?php
namespace App\Services\AI;

use App\Models\Tenant;

/**
 * Works out which party on a scanned invoice is the *counterparty* — the vendor
 * on a purchase bill — as opposed to the tenant's own company.
 *
 * A GST tax invoice always has two parties: the SELLER/supplier who issued it
 * and the BUYER/recipient. When the buyer scans a purchase bill, the party to
 * record is the SELLER — never themselves. Invoice OCR returns both blocks
 * (seller_* and buyer_*); this picks the seller and, as a safety net, swaps to
 * the other block when the picked one is actually the tenant's own company
 * (the model sometimes reads the "Bill To" buyer block as the party). A vendor
 * and buyer can never be the same entity.
 */
class VendorResolver
{
    /**
     * Normalized vendor block for a purchase.
     *
     * @return array{name:?string, gstin:?string, pan:?string, address:?string, email:?string, phone:?string, role:string}
     */
    public static function forPurchase(array $ex, ?Tenant $tenant = null): array
    {
        $seller = self::block($ex, 'seller');
        $buyer  = self::block($ex, 'buyer');

        // Backward-compat: older extractions carried a single party_* block.
        if (self::isEmpty($seller) && self::isEmpty($buyer)) {
            $seller = self::block($ex, 'party');
        }

        // The vendor is the seller (the party that issued the bill).
        $pickedSeller = ! self::isEmpty($seller);
        $vendor = $pickedSeller ? $seller : $buyer;
        $role   = $pickedSeller ? 'seller' : 'buyer';

        // Self-guard: the vendor is NEVER the tenant's own company. If the
        // chosen block is us, use the other one instead — this corrects the
        // common OCR mistake of reading the buyer ("Bill To") block as the party.
        if ($tenant && self::isSelf($vendor, $tenant)) {
            $alt     = $pickedSeller ? $buyer : $seller;
            $altRole = $pickedSeller ? 'buyer' : 'seller';
            if (! self::isEmpty($alt) && ! self::isSelf($alt, $tenant)) {
                $vendor = $alt;
                $role   = $altRole;
            }
        }

        $vendor['role'] = $role;

        return $vendor;
    }

    /** True when this party block is the tenant itself — by GSTIN, PAN, or exact name. */
    public static function isSelf(array $block, Tenant $tenant): bool
    {
        $gstin  = strtoupper(trim((string) ($block['gstin'] ?? '')));
        $tGstin = strtoupper(trim((string) ($tenant->gstin ?? '')));
        if ($gstin !== '' && $tGstin !== '' && $gstin === $tGstin) {
            return true;
        }

        // Same PAN (GSTIN chars 3–12) also means us — catches a scan whose GSTIN
        // digits were misread, and a different-state branch of the same company.
        $pan  = self::panOf($block['pan'] ?? null, $gstin);
        $tPan = self::panOf($tenant->pan ?? null, $tGstin);
        if ($pan !== null && $tPan !== null && $pan === $tPan) {
            return true;
        }

        $name  = mb_strtolower(trim((string) ($block['name'] ?? '')));
        $tName = mb_strtolower(trim((string) ($tenant->name ?? '')));
        return $name !== '' && $tName !== '' && $name === $tName;
    }

    private static function panOf(?string $pan, string $gstin): ?string
    {
        $pan = strtoupper(trim((string) $pan));
        if ($pan !== '') {
            return $pan;
        }
        return strlen($gstin) >= 12 ? substr($gstin, 2, 10) : null;
    }

    /** Pull a party block from the new seller_/buyer_ keys or legacy party_ keys. */
    private static function block(array $ex, string $role): array
    {
        return [
            'name'    => $ex["{$role}_name"]    ?? null,
            'gstin'   => $ex["{$role}_gstin"]   ?? null,
            'pan'     => $ex["{$role}_pan"]     ?? null,
            'address' => $ex["{$role}_address"] ?? null,
            'email'   => $ex["{$role}_email"]   ?? null,
            'phone'   => $ex["{$role}_phone"]   ?? null,
        ];
    }

    private static function isEmpty(array $block): bool
    {
        return trim((string) ($block['name'] ?? '')) === ''
            && trim((string) ($block['gstin'] ?? '')) === '';
    }
}
