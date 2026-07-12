@extends('layouts.app')
@section('title', 'Seedha Bill Connector')
@section('page-title', 'Seedha Bill Connector')

@section('content')
<div class="py-4 space-y-6" x-data="{ showForm: false }">

    {{-- Intro --}}
    <div class="bg-gradient-to-br from-navy-50 to-blue-50 rounded-xl border border-navy-100 p-6">
        <div class="flex items-start gap-3">
            <i class="fa fa-plug text-navy-600 text-xl mt-0.5"></i>
            <div>
                <h2 class="font-semibold text-navy-800">Sync invoices from Seedha Bill</h2>
                <p class="text-sm text-navy-700 mt-1 max-w-2xl">
                    Generate an access token, paste it into Seedha Bill, and your invoices flow into Lekhya automatically.
                    Every imported bill lands in the <strong>Import Queue</strong> for review — nothing posts to your ledger until you approve it.
                </p>
                <a href="{{ route('marketing.connector') }}" target="_blank" class="inline-block mt-2 text-sm text-navy-600 font-medium hover:underline">
                    Read the setup guide <i class="fa fa-arrow-up-right-from-square text-xs ml-0.5"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Flash: freshly generated token (shown once) --}}
    @if(session('token_generated'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-5" x-data="{ copied: false }">
        <p class="text-sm font-semibold text-green-800 mb-2"><i class="fa fa-circle-check mr-1.5"></i>Token generated — copy it now, it won't be shown again.</p>
        <div class="flex items-center gap-2">
            <code x-ref="tok" class="flex-1 bg-white border border-green-200 rounded-lg px-3 py-2 text-sm font-mono text-gray-800 break-all">{{ session('token_generated') }}</code>
            <button @click="navigator.clipboard.writeText($refs.tok.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                    class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium whitespace-nowrap">
                <span x-show="!copied"><i class="fa fa-copy mr-1"></i>Copy</span>
                <span x-show="copied"><i class="fa fa-check mr-1"></i>Copied</span>
            </button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Access tokens --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex items-center justify-between border-b border-gray-100">
            <div>
                <h3 class="font-semibold text-gray-800 text-sm">Access Tokens</h3>
                <p class="text-xs text-gray-400 mt-0.5">Each token authorises one Seedha Bill account to push invoices.</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('connector.sync') }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium">
                        <i class="fa fa-rotate mr-1"></i>Sync now
                    </button>
                </form>
                <button @click="showForm = !showForm" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white rounded-lg text-sm font-semibold">
                    <i class="fa fa-plus mr-1"></i>New Token
                </button>
            </div>
        </div>

        {{-- Generate form --}}
        <div x-show="showForm" x-cloak class="px-5 py-4 bg-gray-50 border-b border-gray-100">
            <form method="POST" action="{{ route('connector.tokens.generate') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                    <input type="text" name="label" required placeholder="e.g. Seedha Bill — main store" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-64">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Expires in (days)</label>
                    <input type="number" name="expires_days" min="1" max="365" placeholder="never" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32">
                </div>
                <button type="submit" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white rounded-lg text-sm font-semibold">Generate</button>
            </form>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Label</th>
                    <th class="text-left px-5 py-2.5">Created</th>
                    <th class="text-left px-5 py-2.5">Expires</th>
                    <th class="text-left px-5 py-2.5">Last used</th>
                    <th class="text-left px-5 py-2.5">Status</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($tokens as $t)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $t->label }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $t->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $t->expires_at ? $t->expires_at->format('d M Y') : 'Never' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $t->last_used_at ? $t->last_used_at->diffForHumans() : '—' }}</td>
                    <td class="px-5 py-3">
                        @php $revoked = ! $t->is_active; $expired = $t->expires_at && $t->expires_at->isPast(); @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $revoked ? 'bg-red-100 text-red-700' : ($expired ? 'bg-gray-100 text-gray-600' : 'bg-green-100 text-green-700') }}">
                            {{ $revoked ? 'Revoked' : ($expired ? 'Expired' : 'Active') }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if($t->is_active)
                        <form method="POST" action="{{ route('connector.tokens.revoke', $t) }}" onsubmit="return confirm('Revoke “{{ $t->label }}”? Sync stops immediately.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-medium">Revoke</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No tokens yet — generate one to connect Seedha Bill.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Connections --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex items-center justify-between border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 text-sm">Connected Sources</h3>
            <a href="{{ route('connector.queue') }}" class="text-sm text-navy-600 font-medium hover:underline">Import Queue <i class="fa fa-arrow-right text-xs"></i></a>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Source</th>
                    <th class="text-left px-5 py-2.5">Mode</th>
                    <th class="text-left px-5 py-2.5">Status</th>
                    <th class="text-left px-5 py-2.5">Last sync</th>
                    <th class="text-right px-5 py-2.5">Invoices synced</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($connections as $c)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $c->source_label ?: 'Seedha Bill' }}</td>
                    <td class="px-5 py-3 text-gray-500 uppercase text-xs">{{ $c->mode ?: 'mock' }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $c->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($c->status ?: 'idle') }}</span>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $c->last_sync_at ? $c->last_sync_at->diffForHumans() : '—' }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">{{ $c->invoices_synced ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No sources connected yet. Generate a token, then run “Sync now” to pull invoices.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
