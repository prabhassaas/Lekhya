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
use Illuminate\Support\Facades\Log;

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

        $tenant    = auth()->user()->tenant;
        $aiCredits = [
            'used'      => $tenant->aiCreditsUsed(),
            'limit'     => $tenant->aiCreditLimit(),
            'remaining' => $tenant->aiCreditsRemaining(),
            'unlimited' => $tenant->aiCreditsUnlimited(),
        ];

        return view('ai.index', compact('pending', 'history', 'driverName', 'aiOnline', 'aiCredits'));
    }

    /** True when the tenant has used up its monthly AI allowance. */
    private function creditsExhausted(): bool
    {
        return (bool) auth()->user()->tenant?->aiCreditsExhausted();
    }

    public function extractInvoice(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:10240']);
        if ($this->creditsExhausted()) {
            return back()->withErrors(['file' => "You've used all your AI credits for this month. Upgrade your plan for more scans."]);
        }
        $tenantId = auth()->user()->tenant_id;
        $file     = $request->file('file');
        $path     = $file->store("ai-uploads/{$tenantId}");

        $result = $this->ai->extractFromFile($file);

        if (isset($result['error'])) {
            return back()->withErrors(['file' => $this->friendlyExtractError((string) $result['error'])]);
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
        if ($this->creditsExhausted()) {
            return response()->json(['error' => "Monthly AI credits used up — upgrade your plan for more."], 429);
        }
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
        if ($this->creditsExhausted()) {
            return response()->json(['error' => "Monthly AI credits used up — upgrade your plan for more."], 429);
        }

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
            $tenantId    = $suggestion->tenant_id;
            $invoiceType = 'purchase';

            // Never let party auto-creation crash the approval. Bad OCR data
            // (over-long/garbled fields) must degrade to a manual pick, not a 500.
            try {
                // Direction: if WE are the seller it's our sales invoice (party =
                // customer); if we're billed it's a purchase (party = vendor).
                $party       = VendorResolver::resolve($suggestion->suggestion ?? [], Tenant::find($tenantId));
                $invoiceType = ($party['direction'] ?? 'purchase') === 'sales' ? 'sales' : 'purchase';
                $partyType   = $party['party_type'] ?? 'vendor';
                $gstin       = strtoupper(trim((string) ($party['gstin'] ?? '')));

                // On a purchase, a same-name/PAN party with a DIFFERENT GSTIN means
                // "separate vendor or branch?" — ask before merging/duplicating.
                if ($invoiceType === 'purchase' && $gstin !== '') {
                    $exact = Party::where('tenant_id', $tenantId)->whereRaw('UPPER(gstin) = ?', [$gstin])->first();
                    if (! $exact && ($dupe = $this->findDuplicateParty($party, $tenantId))) {
                        return redirect()->route('ai.resolve', ['suggestion' => $suggestion->id, 'existing' => $dupe->id]);
                    }
                }

                // Make sure the counterparty exists in the party list so no detail is lost.
                $this->resolveOrCreateParty($suggestion->suggestion ?? [], $tenantId, $partyType);
            } catch (\Throwable $e) {
                Log::warning('AI approve: party auto-create failed', ['suggestion' => $suggestion->id, 'error' => $e->getMessage()]);
            }

            return redirect()->route('accounting.invoices.create', ['type' => $invoiceType, 'ai_suggestion' => $suggestion->id])
                ->with('success', 'Approved — details pre-filled. Verify and post.');
        }

        return back()->with('success', 'Suggestion approved.');
    }

    /**
     * Find an existing party that is likely the SAME company (same name or PAN)
     * but registered under a DIFFERENT GSTIN — the "is this a branch?" case.
     */
    private function findDuplicateParty(array $vendor, int $tenantId): ?Party
    {
        $gstin = strtoupper(trim((string) ($vendor['gstin'] ?? '')));
        $name  = mb_strtolower(trim((string) ($vendor['name'] ?? '')));
        $pan   = strtoupper(trim((string) ($vendor['pan'] ?? ''))) ?: (strlen($gstin) >= 12 ? substr($gstin, 2, 10) : '');
        if ($name === '' && $pan === '') {
            return null;
        }

        return Party::where('tenant_id', $tenantId)
            ->where(function ($q) use ($name, $pan) {
                if ($name !== '') {
                    $q->whereRaw('LOWER(name) = ?', [$name]);
                }
                if ($pan !== '') {
                    $q->orWhereRaw('UPPER(pan) = ?', [$pan]);
                }
            })
            ->where(function ($q) use ($gstin) {
                $q->whereNull('gstin')->orWhereRaw('UPPER(gstin) != ?', [$gstin]);
            })
            ->first();
    }

    /** GET: choose how to record a bill whose vendor collides with an existing one. */
    public function resolveDuplicate(Request $request, AiSuggestion $suggestion)
    {
        $this->authorizeSuggestion($suggestion);
        $existing = Party::where('tenant_id', $suggestion->tenant_id)->findOrFail($request->query('existing'));
        $vendor   = VendorResolver::forPurchase($suggestion->suggestion ?? [], auth()->user()->tenant);

        return view('accounting.parties.resolve', compact('suggestion', 'existing', 'vendor'));
    }

    /** POST: apply the branch / separate / existing choice, then go to the invoice. */
    public function storeResolve(Request $request, AiSuggestion $suggestion)
    {
        $this->authorizeSuggestion($suggestion);
        $data     = $request->validate([
            'choice'   => 'required|in:branch,separate,existing',
            'existing' => 'required|integer',
            'label'    => 'nullable|string|max:100',
        ]);
        $tenantId = $suggestion->tenant_id;
        $existing = Party::where('tenant_id', $tenantId)->findOrFail($data['existing']);
        $vendor   = VendorResolver::forPurchase($suggestion->suggestion ?? [], auth()->user()->tenant);
        $gstin    = strtoupper(trim((string) ($vendor['gstin'] ?? '')));
        $gstin    = strlen($gstin) === 15 ? $gstin : '';

        $params = ['type' => 'purchase', 'ai_suggestion' => $suggestion->id];

        if ($data['choice'] === 'branch') {
            $pan = ! empty($vendor['pan']) ? strtoupper(trim((string) $vendor['pan'])) : ($gstin !== '' ? substr($gstin, 2, 10) : null);
            $branch = $existing->branches()->create([
                'tenant_id'  => $tenantId,
                'label'      => $this->clip($data['label'] ?: 'Branch', 255),
                'gstin'      => $gstin !== '' ? $gstin : null,
                'pan'        => ($pan && strlen($pan) === 10) ? $pan : null,
                'email'      => $this->clip($vendor['email'] ?? null, 255),
                'phone'      => $this->clip($vendor['phone'] ?? null, 15),
                'address'    => $this->clip($vendor['address'] ?? null, 255),
                'state_code' => $gstin !== '' ? substr($gstin, 0, 2) : null,
            ]);
            $params['party_id']        = $existing->id;
            $params['party_branch_id'] = $branch->id;
            $msg = "Recorded as a branch of “{$existing->name}”.";
        } elseif ($data['choice'] === 'separate') {
            $party = $this->createPartyFromVendor($vendor, $tenantId, 'vendor');
            $params['party_id'] = $party->id;
            $msg = "Created “{$party->name}” as a separate vendor.";
        } else {
            $params['party_id'] = $existing->id;
            $msg = "Using existing vendor “{$existing->name}”.";
        }

        return redirect()->route('accounting.invoices.create', $params)->with('success', $msg . ' Verify and post.');
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

        return $this->createPartyFromVendor([
            'name'    => $name,
            'gstin'   => $gstin,
            'pan'     => $ex['party_pan'] ?? null,
            'email'   => $ex['party_email'] ?? null,
            'phone'   => $ex['party_phone'] ?? null,
            'address' => $ex['party_address'] ?? null,
        ], $tenantId, $type);
    }

    /** Create a party straight from a normalized vendor block (no matching). */
    private function createPartyFromVendor(array $vendor, int $tenantId, string $type): Party
    {
        // Clean before write. OCR can misread a long string as a GSTIN/PAN/phone;
        // storing it overflows the column and MySQL 500s. A real GSTIN is exactly
        // 15 chars and a PAN exactly 10 — anything else is a misread, so drop it.
        $gstin = strtoupper(trim((string) ($vendor['gstin'] ?? '')));
        $gstin = strlen($gstin) === 15 ? $gstin : '';
        $name  = trim((string) ($vendor['name'] ?? ''));

        $stateCode = $gstin !== '' ? substr($gstin, 0, 2) : null;
        $pan       = ! empty($vendor['pan']) ? strtoupper(trim((string) $vendor['pan']))
                     : ($gstin !== '' ? substr($gstin, 2, 10) : null);
        $pan       = ($pan && strlen($pan) === 10) ? $pan : null;

        return Party::create([
            'tenant_id'  => $tenantId,
            'type'       => $type,
            'name'       => $this->clip($name !== '' ? $name : 'Unnamed Vendor', 255),
            'gstin'      => $gstin !== '' ? $gstin : null,
            'pan'        => $pan,
            'email'      => $this->clip($vendor['email'] ?? null, 255),
            'phone'      => $this->clip($vendor['phone'] ?? null, 15),
            'address'    => $this->clip($vendor['address'] ?? null, 255),
            'state_code' => $stateCode,
            'is_active'  => true,
        ]);
    }

    /** Trim + cap a value to a column length, returning null when empty. */
    private function clip(mixed $value, int $length): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    /** Turn a raw extraction error into plain guidance for the user. */
    private function friendlyExtractError(string $error): string
    {
        $e = strtolower($error);
        return match (true) {
            str_contains($e, 'json') || str_contains($e, 'parse')
                => "Couldn't read this file clearly. Upload a sharper photo or the original PDF and try again.",
            str_contains($e, 'could not extract text')
                => "This file had no readable text. Use a clear PDF or a well-lit, straight photo of the invoice.",
            str_contains($e, 'groq') || str_contains($e, 'api error') || str_contains($e, 'unavailable')
                => "The AI reader had trouble with this bill. Please try again in a moment.",
            default => $error,
        };
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
