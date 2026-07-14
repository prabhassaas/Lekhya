{{-- ── Floating module-aware AI assistant (authenticated users only) ─────── --}}
@php
    $__r = request()->route()?->getName() ?? '';
    [$__mod, $__scope] = match (true) {
        str_starts_with($__r, 'accounting.invoices')  => ['Invoices', 'This area is for creating, AI-scanning and posting sales & purchase invoices.'],
        str_starts_with($__r, 'accounting.parties')   => ['Vendors & Customers', 'This area manages parties — their GSTIN, bank details, classification and outstanding balances.'],
        str_starts_with($__r, 'accounting.payments')  => ['Payments', 'This area shows pending payables/receivables and generates bank payment files.'],
        str_starts_with($__r, 'accounting.products')  => ['Inventory', 'This area manages products, their HSN/SAC codes, GST rates and stock.'],
        str_starts_with($__r, 'accounting.journals')  => ['Journal Vouchers', 'This area is for manual double-entry journals and reversals.'],
        str_starts_with($__r, 'accounting.accounts')  => ['Chart of Accounts', 'This area manages ledger accounts and their balances.'],
        str_starts_with($__r, 'accounting.reports')   => ['Reports', 'This area has P&L, Balance Sheet, Trial Balance and AR/AP aging.'],
        str_starts_with($__r, 'accounting.tally')     => ['Tally Migration', 'This area imports data from Tally.'],
        str_starts_with($__r, 'banking.')             => ['Banking', 'This area manages bank accounts and reconciling statements against the books.'],
        str_starts_with($__r, 'gst.')                 => ['GST & Returns', 'This area handles GSTIN validation, GSTR-1/3B/2B and e-invoicing.'],
        str_starts_with($__r, 'connector.')           => ['Seedha Bill Connector', 'This area imports bills from Seedha Bill into Lekhya.'],
        str_starts_with($__r, 'ai.')                  => ['AI Assistant', 'This area covers AI invoice scanning, credits and history.'],
        str_starts_with($__r, 'pramaan.')             => ['Pramaan — CA suite', 'This area is the CA edition: multi-client, UDIN, audit reports, DSC vault and compliance calendar.'],
        str_starts_with($__r, 'settings.')            => ['Settings', 'This area covers company profile, users, billing and preferences.'],
        $__r === 'dashboard'                          => ['Dashboard', 'This is the overview of sales, purchases, cash position and GST.'],
        default                                       => ['Lekhya', 'General GST accounting help.'],
    };
    $__first = strtok(trim((string) auth()->user()->name), ' ') ?: 'there';
@endphp

<div x-data="lekhyaAssistant(@js($__mod), @js($__scope), @js($__first))" x-cloak>
    {{-- Floating toggle (bottom-left, clear of the calculator) --}}
    <button type="button" x-show="!open" @click="toggle()" title="Ask Lekhya AI"
            style="position:fixed;left:20px;bottom:20px;z-index:9000;height:52px"
            class="rounded-full bg-navy-600 hover:bg-navy-700 text-white shadow-lg flex items-center gap-2 pl-4 pr-5 transition hover:scale-105">
        <i class="fa fa-wand-magic-sparkles"></i><span class="text-sm font-medium hidden sm:inline">Ask AI</span>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-transition
         style="position:fixed;left:16px;bottom:16px;z-index:9001;width:clamp(260px,92vw,340px);max-height:76vh"
         class="bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-2.5 bg-navy-600 text-white">
            <div class="min-w-0">
                <p class="text-sm font-semibold leading-tight flex items-center gap-2"><i class="fa fa-wand-magic-sparkles text-xs opacity-80"></i>Lekhya AI</p>
                <p class="text-[11px] text-navy-200 truncate">Helping with <span x-text="module"></span></p>
            </div>
            <button type="button" @click="open=false" class="w-6 h-6 rounded hover:bg-white/20 flex items-center justify-center"><i class="fa fa-xmark text-sm"></i></button>
        </div>

        {{-- Thread --}}
        <div x-ref="thread" class="flex-1 overflow-y-auto p-3 space-y-2.5 bg-gray-50 text-sm">
            <template x-if="messages.length === 0">
                <div>
                    <p class="text-gray-700">Hi <span class="font-semibold" x-text="firstName"></span> 👋</p>
                    <p class="text-gray-500 mt-1">I can help you with <span class="font-medium text-gray-700" x-text="module"></span>. Ask me anything about this screen.</p>
                    <div class="mt-3 space-y-1.5">
                        <template x-for="(s, i) in suggestions()" :key="i">
                            <button type="button" @click="send(s)" class="block w-full text-left text-xs px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-navy-300 hover:bg-navy-50">
                                <i class="fa fa-arrow-right text-[10px] text-gray-300 mr-1.5"></i><span x-text="s"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
            <template x-for="(m, i) in messages" :key="i">
                <div :class="m.role === 'user' ? 'text-right' : 'text-left'">
                    <span :class="m.role === 'user' ? 'bg-navy-600 text-white' : 'bg-white border border-gray-200 text-gray-800'"
                          class="inline-block px-3 py-2 rounded-2xl max-w-[85%] text-left whitespace-pre-wrap" x-text="m.text"></span>
                </div>
            </template>
            <div x-show="busy" class="text-left"><span class="inline-block px-3 py-2 rounded-2xl bg-white border border-gray-200 text-gray-400"><i class="fa fa-circle-notch fa-spin"></i></span></div>
        </div>

        {{-- Input --}}
        <div class="p-2.5 border-t border-gray-100 bg-white">
            <form @submit.prevent="send()" class="flex items-center gap-2">
                <input type="text" x-model="input" :disabled="busy" placeholder="Ask about this screen…"
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-navy-400 focus:ring-1 focus:ring-navy-200 outline-none">
                <button type="submit" :disabled="busy || !input.trim()" class="w-9 h-9 rounded-lg bg-navy-600 hover:bg-navy-700 disabled:opacity-40 text-white flex items-center justify-center"><i class="fa fa-paper-plane text-xs"></i></button>
            </form>
            <p class="text-[10px] text-gray-400 mt-1.5 flex items-center justify-between">
                <span>Each answer uses 1 AI credit.</span>
                <a href="{{ route('ai.credits') }}" class="text-navy-500 hover:underline" x-show="remaining !== null">
                    <span x-show="!unlimited"><span x-text="remaining"></span> left</span>
                    <span x-show="unlimited">Unlimited</span>
                </a>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('lekhyaAssistant', function (module, scope, firstName) {
        return {
            open: false, busy: false, input: '',
            module: module, scope: scope, firstName: firstName,
            messages: [], remaining: null, unlimited: false,
            toggle() { this.open = !this.open; },
            suggestions() {
                var m = (this.module || '').toLowerCase();
                if (m.indexOf('invoice') > -1) return ['How do I scan a purchase bill?', 'Is GST inclusive or exclusive here?', 'How do I reverse a posted bill?'];
                if (m.indexOf('gst') > -1) return ['How do I generate GSTR-1?', 'What is GSTR-2B reconciliation?', 'How do I validate a GSTIN?'];
                if (m.indexOf('bank') > -1) return ['How does reconciliation work?', 'How do I import a statement?'];
                if (m.indexOf('payment') > -1) return ['How do I make a bank payment file?', 'How is TDS deducted?'];
                if (m.indexOf('vendor') > -1 || m.indexOf('customer') > -1) return ['How are vendors classified?', 'How do I add bank details?'];
                if (m.indexOf('inventory') > -1) return ['How does HSN auto-map work?', 'How do I track stock?'];
                if (m.indexOf('report') > -1) return ['How do I export a P&L?', 'What is AR/AP aging?'];
                return ['How do I record a sale?', 'How do I record a purchase bill?', 'Where do I file GST returns?'];
            },
            send(preset) {
                var msg = (preset || this.input).trim();
                if (!msg || this.busy) return;
                this.messages.push({ role: 'user', text: msg });
                this.input = ''; this.busy = true;
                this.$nextTick(() => this.down());
                fetch(@js(route('ai.ask')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: JSON.stringify({ message: msg, module: this.module, scope: this.scope })
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                  .then((res) => {
                      this.busy = false;
                      this.messages.push({ role: 'ai', text: res.ok ? res.j.answer : (res.j.error || 'Sorry, something went wrong. Please try again.') });
                      if (res.j.remaining !== undefined) { this.remaining = res.j.remaining; this.unlimited = res.j.unlimited; }
                      this.$nextTick(() => this.down());
                  }).catch(() => { this.busy = false; this.messages.push({ role: 'ai', text: 'Network error — please try again.' }); this.$nextTick(() => this.down()); });
            },
            down() { var e = this.$refs.thread; if (e) e.scrollTop = e.scrollHeight; },
        };
    });
});
</script>
