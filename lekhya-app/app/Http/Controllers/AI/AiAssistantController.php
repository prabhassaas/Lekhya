<?php
namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiSuggestion;
use App\Models\Party;
use App\Services\AI\AiService;
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

        AiSuggestion::create([
            'tenant_id'     => $tenantId,
            'type'          => 'extraction',
            'input_context' => ['file_path' => $path, 'filename' => $file->getClientOriginalName()],
            'suggestion'    => $result,
            'status'        => 'pending',
            'model_used'    => config('services.ai.model'),
            'model_metadata'=> ['driver' => $this->ai->getDriverName(), 'is_mock' => $result['_mock'] ?? false],
        ]);

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

        return response()->json(['success' => true, 'result' => $result]);
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
