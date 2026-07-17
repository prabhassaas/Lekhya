<?php
namespace App\Http\Controllers\Connector;
use App\Http\Controllers\Controller;
use App\Models\{ConnectorToken, ConnectorConnection, ConnectorImportQueue, ConnectorEvent};
use App\Services\Connector\{SeedhaBillAdapter, ImportPipeline};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ConnectorController extends Controller {
    public function index() {
        $tenantId = auth()->user()->tenant_id;
        $tenant   = auth()->user()->tenant;
        $tokens = ConnectorToken::where('tenant_id', $tenantId)->with('createdBy')->latest()->get();
        $connections = ConnectorConnection::where('tenant_id', $tenantId)->latest()->get();
        $connLimit = [
            'used'      => $tenant->seedhaBillConnectionsUsed(),
            'limit'     => $tenant->seedhaBillConnectionLimit(),
            'unlimited' => $tenant->seedhaBillConnectionsUnlimited(),
            'plan'      => $tenant->activePlan()?->name,
        ];
        return view('connector.index', compact('tokens', 'connections', 'connLimit'));
    }

    public function generateToken(Request $request) {
        $request->validate(['label' => 'required|string|max:100', 'expires_days' => 'nullable|integer|min:1|max:365']);
        $tenantId = auth()->user()->tenant_id;
        $tenant   = auth()->user()->tenant;
        // One Seedha Bill account = one active token. The plan sets how many a
        // company may run at once — block over-limit and point to the upgrade.
        if (! $tenant->canAddSeedhaBillConnection()) {
            $limit = $tenant->seedhaBillConnectionLimit();
            return back()->with('error', "Your plan allows {$limit} Seedha Bill connection" . ($limit === 1 ? '' : 's') . ". Revoke an existing one or upgrade your plan to connect more.");
        }
        $rawToken = 'LKY-' . Str::random(32);
        ConnectorToken::create([
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'token_hash' => hash('sha256', $rawToken),
            'label' => $request->label,
            'scope' => ['read:invoices'],
            'expires_at' => $request->expires_days ? now()->addDays($request->expires_days) : null,
        ]);
        ConnectorEvent::create(['tenant_id' => $tenantId, 'event_type' => 'token.generated', 'description' => "Token '{$request->label}' generated", 'actor_id' => auth()->id()]);
        return back()->with('token_generated', $rawToken)->with('success', 'Token generated. Copy it now — it will not be shown again.');
    }

    public function revokeToken(ConnectorToken $token) {
        abort_if($token->tenant_id !== auth()->user()->tenant_id, 403);
        $token->update(['is_active' => false, 'revoked_by' => auth()->id(), 'revoked_at' => now()]);
        ConnectorEvent::create(['tenant_id' => auth()->user()->tenant_id, 'event_type' => 'token.revoked', 'description' => "Token '{$token->label}' revoked", 'actor_id' => auth()->id()]);
        // Disable all connections using this token
        ConnectorConnection::where('connector_token_id', $token->id)->update(['status' => 'revoked']);
        return back()->with('success', 'Token revoked. Sync stopped immediately.');
    }

    public function queue() {
        $tenantId = auth()->user()->tenant_id;
        $items = ConnectorImportQueue::where('tenant_id', $tenantId)
            ->whereIn('status', ['quarantined', 'pending', 'validated'])
            ->latest()
            ->paginate(20);
        return view('connector.queue', compact('items'));
    }

    public function approveQueued(ConnectorImportQueue $item, Request $request) {
        abort_if($item->tenant_id !== auth()->user()->tenant_id, 403);
        $item->update(['status' => 'validated', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return back()->with('success', 'Item approved for posting.');
    }

    public function rejectQueued(ConnectorImportQueue $item) {
        abort_if($item->tenant_id !== auth()->user()->tenant_id, 403);
        $item->update(['status' => 'skipped', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return back()->with('success', 'Item rejected and skipped.');
    }

    public function triggerSync(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $adapter = new SeedhaBillAdapter('mock');
        $pipeline = app(ImportPipeline::class);
        $results = $pipeline->run($tenantId, 'seedha_bill', 'mock', auth()->id());
        return back()->with('success', "Sync complete: {$results['posted']} posted, {$results['quarantined']} quarantined, {$results['duplicate']} duplicates.");
    }

    public function webhook(Request $request) {
        $tokenRaw = $request->bearerToken();
        if (!$tokenRaw) return response()->json(['error' => 'Unauthorized'], 401);
        $token = ConnectorToken::where('token_hash', hash('sha256', $tokenRaw))->where('is_active', true)->first();
        if (!$token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }
        $token->update(['last_used_at' => now()]);
        // Process incoming invoices
        $payload = $request->json()->all();
        ConnectorImportQueue::create([
            'tenant_id' => $token->tenant_id,
            'source' => 'seedha_bill',
            'external_id' => $payload['id'] ?? Str::uuid(),
            'raw_payload' => $payload,
            'status' => 'pending',
        ]);
        return response()->json(['status' => 'queued']);
    }
}
