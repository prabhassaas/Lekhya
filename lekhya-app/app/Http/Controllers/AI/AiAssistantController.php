<?php
namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiSuggestion;
use App\Models\AiUsage;
use App\Models\Party;
use App\Models\Tenant;
use App\Services\AI\AiService;
use App\Services\AI\VendorResolver;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __construct(private readonly AiService $ai) {}

    public function index()
    {
        $tenantId   = auth()->user()->tenant_id;
        $pending    = AiSuggestion::where('tenant_id', $tenantId)->where('status', 'pending')->latest()->paginate(10, ['*'], 'pending_page');
        $history    = AiSuggestion::where('tenant_id', $tenantId)->whereIn('status', ['approved', 'rejected'])->latest()->paginate(10, ['*'], 'history_page');
        $driverName = $this->ai->getDriverName();
        $aiOnline   = $this->ai->isAvailable();

        return view('ai.index', compact('pending', 'history', 'driverName', 'aiOnline'));
    }

    public function extractInvoice(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:10240']);
        $tenantId = auth()->user()->tenant_id;
        $file     = $request->file('file');
        $path     = $file->store("ai-uploads/{$tenantId}");

        $result = $this->ai->extractFromFile($file);

        if (isset($result['error'])) {
            return back()->withErrors(['file' => $result['error']]);
        }

        // A scanned bill is a PURCHASE: the party we record is the SELLER
        // (supplier), never ourselves. Normalize the seller/buyer blocks the
        // model returned into the party_* fields every downstream consumer reads,
        // with a self-guard so the tenant's own company is never taken as vendor.
        $result = $this->normalizeVendor($result, auth()->user()->tenant);

        AiSuggestion::create([
            'tenant_id'     => $tenantId,
            'type'          => 'extraction',
            'input_context' => ['file_path' => $path, 'filename' => $file->getClientOriginalName()],
            'suggestion'    => $result,
            'status'        => 'pending',
            'model_used'    => config('services.ai.model'),
            'model_metadata'=> ['driver' => $this->ai->getDriverName(), 'is_mock' => $result['_mock'] ?? false],
        ]);

        $this->meter($tenantId, 'extraction');

        // Land on the review page regardless of where the upload came from
        // (AI page, invoices page, or a phone camera capture).
        return redirect()->route('ai.index')->with('success', "Invoice read from \"{$file->getClientOriginalName()}\". Review and approve the suggestion below.");
    }

    public function naturalLanguageQuery(Request $request)
    {
        $request->validate(['query' => 'required|string|max:500']);
        $tenantId = auth()->user()->tenant_id;

        $result = $this->ai->runNlQuery($request->query, $tenantId);

        $suggestion = AiSuggestion::create([
            'tenant_id'     => $tenantId,
            'type'          => 'nl_query',
            'input_context' => ['query' => $request->query],
            'suggestion'    => $result,
            'status'        => 'pending',
            'model_used'    => config('services.ai.model'),
            'model_metadata'=> ['driver' => $this->ai->getDriverName()],
        ]);

        $this->meter($tenantId, 'nl_query');

        return response()->json([
            'success'       => true,
            'result'        => $result,
            'suggestion_id' => $suggestion->id,
        ]);
    }

    public function suggestAccount(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0',
            'vendor'      => 'nullable|string|max:255',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $result   = $this->ai->suggestAccount($request->description, (float) $request->amount, $request->vendor ?? '');

        AiSuggestion::create([
            'tenant_id'     => $tenantId,
            'type'          => 'account_coding',
            'input_context' => $request->only('description', 'amount', 'vendor'),
            'suggestion'    => $result,
            'status'        => 'pending',
            'model_used'    => config('services.ai.model'),
            'model_metadata'=> ['driver' => $this->ai->getDriverName()],
        ]);

        $this->meter($tenantId, 'account_coding');

        return response()->json(['success' => true, 'result' => $result]);
    }

    /** Record one AI call for per-tenant metering (credits + admin console). */
    private function meter(int $tenantId, string $type): void
    {
        $driver = $this->ai->getDriverName();
        AiUsage::create([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'type'      => $type,
            'driver'    => $driver,
            'billable'  => $driver !== 'mock', // mock/offline calls don't count against credits
        ]);
    }

    public function approve(AiSuggestion $suggestion)
    {
        $this->authorizeSuggestion($suggestion);

        $suggestion->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if ($suggestion->type === 'extraction') {
            // A scanned bill is a purchase — make sure the vendor exists in the
            // party list (matched or freshly created) so no detail is lost.
            $this->resolveOrCreateParty($suggestion->suggestion ?? [], $suggestion->tenant_id, 'vendor');

            return redirect()->route('accounting.invoices.create', ['type' => 'purchase', 'ai_suggestion' => $suggestion->id])
                ->with('success', 'Approved — vendor and full invoice details pre-filled. Verify and post.');
        }

        return back()->with('success', 'Suggestion approved.');
    }

    /**
     * Collapse the seller/buyer blocks into the party_* fields that the review
     * UI, validator, and invoice prefill all read. For a purchase the party is
     * the SELLER; the self-guard in VendorResolver ensures the tenant's own
     * company is never chosen (a vendor and buyer can't be the same entity).
     */
    private function normalizeVendor(array $ex, ?Tenant $tenant): array
    {
        $vendor = VendorResolver::forPurchase($ex, $tenant);

        $ex['party_name']    = $vendor['name'];
        $ex['party_gstin']   = $vendor['gstin'];
        $ex['party_pan']     = $vendor['pan'];
        $ex['party_address'] = $vendor['address'];
        $ex['party_email']   = $vendor['email'];
        $ex['party_phone']   = $vendor['phone'];

        // Carry the chosen party's confidence onto the party_* aliases so the
        // review UI's green/amber logic stays meaningful.
        $fc = $ex['field_confidence'] ?? [];
        if (is_array($fc)) {
            foreach (['name', 'gstin', 'pan', 'address'] as $f) {
                if (isset($fc["{$vendor['role']}_{$f}"])) {
                    $fc["party_{$f}"] = $fc["{$vendor['role']}_{$f}"];
                }
            }
            $ex['field_confidence'] = $fc;
        }

        return $ex;
    }

    /**
     * Match the extracted party to an existing one (by GSTIN, then name) or
     * create it from the invoice's own details. Idempotent — never duplicates.
     */
    private function resolveOrCreateParty(array $ex, int $tenantId, string $type): ?Party
    {
        $gstin = strtoupper(trim((string) ($ex['party_gstin'] ?? '')));
        $name  = trim((string) ($ex['party_name'] ?? ''));
        if ($name === '' && $gstin === '') {
            return null;
        }

        // Hard stop: never create our own company as a vendor. A vendor and the
        // buyer (us) can't be the same entity — if extraction still landed on
        // us, skip rather than pollute the party list with ourselves.
        $tenant = Tenant::find($tenantId);
        if ($tenant && VendorResolver::isSelf(['gstin' => $gstin, 'name' => $name], $tenant)) {
            return null;
        }

        $base = Party::where('tenant_id', $tenantId);
        $match = null;
        if ($gstin !== '') {
            $match = (clone $base)->whereRaw('UPPER(gstin) = ?', [$gstin])->first();
        }
        if (! $match && $name !== '') {
            $match = (clone $base)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        }
        if ($match) {
            return $match;
        }

        // GSTIN encodes the state (first 2 digits) and PAN (chars 3–12).
        $stateCode = strlen($gstin) >= 2 ? substr($gstin, 0, 2) : null;
        $pan       = ! empty($ex['party_pan']) ? strtoupper(trim($ex['party_pan']))
                     : (strlen($gstin) >= 12 ? substr($gstin, 2, 10) : null);

        return Party::create([
            'tenant_id'  => $tenantId,
            'type'       => $type,
            'name'       => $name !== '' ? $name : 'Unnamed Vendor',
            'gstin'      => $gstin !== '' ? $gstin : null,
            'pan'        => $pan,
            'email'      => $ex['party_email'] ?? null,
            'phone'      => $ex['party_phone'] ?? null,
            'address'    => $ex['party_address'] ?? null,
            'state_code' => $stateCode,
            'is_active'  => true,
        ]);
    }

    public function reject(AiSuggestion $suggestion)
    {
        $this->authorizeSuggestion($suggestion);

        $suggestion->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Suggestion rejected.');
    }

    private function authorizeSuggestion(AiSuggestion $suggestion): void
    {
        abort_if($suggestion->tenant_id !== auth()->user()->tenant_id, 403);
    }
}
