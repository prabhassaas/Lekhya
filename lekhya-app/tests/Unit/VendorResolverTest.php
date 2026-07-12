<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\AI\VendorResolver;
use PHPUnit\Framework\TestCase;

/**
 * The vendor on a scanned PURCHASE bill is the SELLER — never the tenant's own
 * company. A vendor and the buyer (us) can't be the same entity. These lock in
 * that rule, including the self-guard that corrects the model swapping the
 * seller/buyer blocks.
 */
class VendorResolverTest extends TestCase
{
    private function tenant(): Tenant
    {
        // No DB needed — a plain (unsaved) model carries the identity fields.
        return new Tenant([
            'name'  => 'The Yarn Story',
            'gstin' => '27CNPPS8883M1ZL',
            'pan'   => 'CNPPS8883M',
        ]);
    }

    public function test_seller_is_the_vendor_when_blocks_are_correct(): void
    {
        $vendor = VendorResolver::forPurchase([
            'seller_name'  => 'MACROTECH SOFTWARES', 'seller_gstin' => '27KDWPS9761R1ZM',
            'buyer_name'   => 'The Yarn Story',      'buyer_gstin'  => '27CNPPS8883M1ZL',
        ], $this->tenant());

        $this->assertSame('MACROTECH SOFTWARES', $vendor['name']);
        $this->assertSame('27KDWPS9761R1ZM', $vendor['gstin']);
        $this->assertSame('seller', $vendor['role']);
    }

    public function test_self_guard_swaps_when_model_puts_us_in_the_seller_slot(): void
    {
        // Model mislabeled: our company landed in seller_*, the real vendor in buyer_*.
        $vendor = VendorResolver::forPurchase([
            'seller_name' => 'The Yarn Story',      'seller_gstin' => '27CNPPS8883M1ZL',
            'buyer_name'  => 'MACROTECH SOFTWARES', 'buyer_gstin'  => '27KDWPS9761R1ZM',
        ], $this->tenant());

        $this->assertSame('MACROTECH SOFTWARES', $vendor['name']);
        $this->assertSame('buyer', $vendor['role']);
    }

    public function test_self_detected_by_name_even_when_gstin_is_misread(): void
    {
        $vendor = VendorResolver::forPurchase([
            'seller_name' => 'the yarn story',      'seller_gstin' => 'GARBAGE',
            'buyer_name'  => 'MACROTECH SOFTWARES', 'buyer_gstin'  => '27KDWPS9761R1ZM',
        ], $this->tenant());

        $this->assertSame('MACROTECH SOFTWARES', $vendor['name']);
    }

    public function test_self_detected_by_shared_pan_across_a_branch_gstin(): void
    {
        // Same PAN, different-state GSTIN (a branch of us) must still count as self.
        $vendor = VendorResolver::forPurchase([
            'seller_name' => 'The Yarn Story (Delhi)', 'seller_gstin' => '07CNPPS8883M1ZH',
            'buyer_name'  => 'MACROTECH SOFTWARES',    'buyer_gstin'  => '27KDWPS9761R1ZM',
        ], $this->tenant());

        $this->assertSame('MACROTECH SOFTWARES', $vendor['name']);
    }

    public function test_legacy_single_party_block_still_resolves(): void
    {
        $vendor = VendorResolver::forPurchase([
            'party_name'  => 'Some Old Vendor Pvt Ltd', 'party_gstin' => '29AABCT1332L1ZV',
        ], $this->tenant());

        $this->assertSame('Some Old Vendor Pvt Ltd', $vendor['name']);
    }

    public function test_is_self_matches_own_gstin_pan_and_name(): void
    {
        $t = $this->tenant();
        $this->assertTrue(VendorResolver::isSelf(['gstin' => '27CNPPS8883M1ZL'], $t));
        $this->assertTrue(VendorResolver::isSelf(['gstin' => '07CNPPS8883M1ZH'], $t)); // same PAN
        $this->assertTrue(VendorResolver::isSelf(['name' => 'THE YARN STORY'], $t));
        $this->assertFalse(VendorResolver::isSelf(['gstin' => '27KDWPS9761R1ZM', 'name' => 'MACROTECH SOFTWARES'], $t));
    }

    public function test_resolve_detects_sales_when_we_are_the_seller(): void
    {
        // Our own sales invoice: we issued it → SALES, counterparty is the customer.
        $r = VendorResolver::resolve([
            'seller_name' => 'The Yarn Story',         'seller_gstin' => '27CNPPS8883M1ZL',
            'buyer_name'  => 'Sun TV Network Limited', 'buyer_gstin'  => '27AAECS8585K1ZX',
        ], $this->tenant());

        $this->assertSame('sales', $r['direction']);
        $this->assertSame('customer', $r['party_type']);
        $this->assertSame('Sun TV Network Limited', $r['name']);
    }

    public function test_resolve_detects_purchase_when_we_are_the_buyer(): void
    {
        $r = VendorResolver::resolve([
            'seller_name' => 'MACROTECH SOFTWARES', 'seller_gstin' => '27KDWPS9761R1ZM',
            'buyer_name'  => 'The Yarn Story',      'buyer_gstin'  => '27CNPPS8883M1ZL',
        ], $this->tenant());

        $this->assertSame('purchase', $r['direction']);
        $this->assertSame('vendor', $r['party_type']);
        $this->assertSame('MACROTECH SOFTWARES', $r['name']);
    }

    public function test_resolve_defaults_to_purchase_when_neither_side_is_us(): void
    {
        $r = VendorResolver::resolve([
            'seller_name' => 'MACROTECH SOFTWARES', 'seller_gstin' => '27KDWPS9761R1ZM',
            'buyer_name'  => 'Some Other Co',       'buyer_gstin'  => '29AAAAA0000A1Z5',
        ], $this->tenant());

        $this->assertSame('purchase', $r['direction']);
        $this->assertSame('MACROTECH SOFTWARES', $r['name']);
    }
}
