@extends('layouts.marketing')
@section('title', 'SeedhaBill Connector Guide — Lekhya Help')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">SeedhaBill Connector</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">SeedhaBill → Lekhya Connector</h1>
    <p class="text-lg text-gray-600 mb-10">Raise an invoice in SeedhaBill, see it in Lekhya automatically. No copy-paste, no data entry.</p>

    {{-- What it does --}}
    <div class="bg-navy-600 rounded-2xl p-6 text-white mb-12">
        <h3 class="font-semibold text-lg mb-4">How it works in one line</h3>
        <div class="flex items-center gap-3 flex-wrap">
            @foreach(['Customer raises invoice in SeedhaBill', '→', 'Lekhya picks it up automatically', '→', 'You review and approve', '→', 'Accounting entries are posted'] as $step)
            @if($step === '→')
            <i class="fa fa-arrow-right text-blue-300 text-lg"></i>
            @else
            <div class="bg-white bg-opacity-10 rounded-lg px-3 py-2 text-sm">{{ $step }}</div>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Flow steps --}}
    <div class="flex gap-0 mb-12 overflow-x-auto">
        @foreach([
            ['Raise Bill', 'You issue an invoice in SeedhaBill as usual', 'file-invoice', '#E07C00'],
            ['Auto Sync', 'Lekhya polls SeedhaBill every few minutes and pulls new invoices', 'rotate', '#1B2A4A'],
            ['Review Queue', 'Open Connector → Review Queue in Lekhya to see incoming invoices', 'list-check', '#7C3AED'],
            ['Approve & Post', 'Click Approve — Lekhya creates the journal entry and GST records', 'check-double', '#059669'],
        ] as [$title, $desc, $icon, $color])
        <div class="flex-1 min-w-[140px] text-center px-2">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-2" style="background:{{ $color }}20;border:2px solid {{ $color }}40">
                <i class="fa fa-{{ $icon }}" style="color:{{ $color }}"></i>
            </div>
            <p class="font-semibold text-gray-900 text-sm">{{ $title }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $desc }}</p>
        </div>
        @if(!$loop->last)
        <div class="flex items-center pt-0 mt-5 text-gray-300 text-lg px-1">›</div>
        @endif
        @endforeach
    </div>

    <div class="space-y-12">

        {{-- Who needs this? --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Who Is This For?</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                    <h4 class="font-semibold text-green-900 mb-2"><i class="fa fa-user mr-1"></i> Same Prabhas Account (Easiest)</h4>
                    <p class="text-sm text-green-800">You use both SeedhaBill and Lekhya with the same login. Connection is one click — just toggle "Connect SeedhaBill" in Lekhya's Connector settings.</p>
                    <p class="text-xs text-green-600 mt-2 font-medium">⟵ Most users fall here</p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                    <h4 class="font-semibold text-blue-900 mb-2"><i class="fa fa-building mr-1"></i> CA / Accountant Setup</h4>
                    <p class="text-sm text-blue-800">Your client uses SeedhaBill. You use Lekhya on their behalf. Your client generates a connection token in SeedhaBill and shares it with you. You paste it in Lekhya once.</p>
                    <p class="text-xs text-blue-600 mt-2 font-medium">⟵ CA firms use this</p>
                </div>
            </div>
        </section>

        {{-- Setup --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Setting Up the Connector</h2>

            <div x-data="{ tab: 'same' }" class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="flex border-b border-gray-100">
                    <button @click="tab='same'" :class="tab==='same' ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                            class="flex-1 py-3 text-sm font-medium transition">Same Account</button>
                    <button @click="tab='ca'" :class="tab==='ca' ? 'bg-navy-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                            class="flex-1 py-3 text-sm font-medium transition">CA / Different Account</button>
                </div>

                <div class="p-6">
                    <div x-show="tab==='same'">
                        <ol class="space-y-4">
                            @foreach([
                                'Sign in to Lekhya with the same Google/email you use for SeedhaBill.',
                                'Go to Connector (left menu).',
                                'Toggle "Connect SeedhaBill" to ON.',
                                'Done. Lekhya will start syncing within 5 minutes.',
                            ] as $i => $step)
                            <li class="flex gap-3">
                                <div class="w-7 h-7 rounded-full bg-navy-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</div>
                                <p class="text-sm text-gray-700 mt-1">{{ $step }}</p>
                            </li>
                            @endforeach
                        </ol>
                    </div>
                    <div x-show="tab==='ca'">
                        <p class="text-sm text-gray-600 mb-4">Your client does steps 1–2 in <strong>SeedhaBill</strong>. You do steps 3–4 in <strong>Lekhya</strong>.</p>
                        <ol class="space-y-4">
                            @foreach([
                                ['Client: SeedhaBill', 'Your client logs in to SeedhaBill → Settings → Integrations → Generate Lekhya Token.'],
                                ['Client: Share Token', 'They copy the token and send it to you securely (WhatsApp, email, etc.).'],
                                ['You: Paste in Lekhya', 'In Lekhya → Connector → "Connect with Token" → paste the token.'],
                                ['You: Verify', 'Lekhya shows the company name. Click Confirm. Sync starts within 5 minutes.'],
                            ] as $i => [$who, $step])
                            <li class="flex gap-3">
                                <div class="w-7 h-7 rounded-full bg-navy-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</div>
                                <div>
                                    <span class="text-xs font-semibold text-navy-600 uppercase">{{ $who }}</span>
                                    <p class="text-sm text-gray-700">{{ $step }}</p>
                                </div>
                            </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        {{-- Review queue --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Using the Review Queue</h2>
            <p class="text-gray-600 mb-4">Incoming invoices land in <strong>Connector → Review Queue</strong> before they're posted to your ledger. This gives you a chance to verify before accounting entries are created.</p>
            <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
                @foreach([
                    ['Review', 'See invoice number, customer, amount, GST breakdown from SeedhaBill.'],
                    ['Approve', 'Creates debit to Accounts Receivable and credit to Sales + GST accounts automatically.'],
                    ['Reject', 'Removes from queue. The original SeedhaBill invoice is not affected.'],
                    ['Bulk Actions', 'Select multiple invoices and approve all at once if you trust the source.'],
                ] as [$action, $desc])
                <div class="flex gap-4 px-5 py-4">
                    <span class="text-sm font-semibold text-navy-600 w-24 flex-shrink-0">{{ $action }}</span>
                    <p class="text-sm text-gray-600">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </section>

        {{-- FAQ --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Common Questions</h2>
            <div x-data="{ open: null }" class="space-y-2">
                @foreach([
                    ['How often does Lekhya sync?', 'Every 5 minutes automatically. You can also trigger a manual sync from the Connector page.'],
                    ['What if I edit an invoice in SeedhaBill after syncing?', 'Edits in SeedhaBill don\'t automatically update posted entries in Lekhya. You\'ll need to reverse and re-approve. This protects the immutability of your ledger.'],
                    ['Does it sync old invoices from before I connected?', 'Yes — on first connect Lekhya pulls the last 90 days by default. You can change this in Connector settings.'],
                    ['What if the same invoice syncs twice?', 'Lekhya de-duplicates on invoice number. The second occurrence is silently ignored.'],
                    ['Can I disconnect?', 'Yes — Connector → Settings → Disconnect. Existing approved invoices in Lekhya are not deleted.'],
                ] as $i => [$q, $a])
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button @click="open = (open === {{ $i }}) ? null : {{ $i }}"
                            class="w-full flex items-center justify-between px-5 py-4 text-left">
                        <span class="font-medium text-gray-900 text-sm">{{ $q }}</span>
                        <i class="fa fa-chevron-down text-gray-400 text-xs transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $i }}" class="px-5 pb-4 text-sm text-gray-600">{{ $a }}</div>
                </div>
                @endforeach
            </div>
        </section>

        <div class="bg-navy-50 border border-navy-200 rounded-xl p-5 text-center">
            <p class="font-semibold text-navy-900 mb-2">Want a full technical walkthrough?</p>
            <a href="{{ route('marketing.connector') }}" class="text-navy-600 hover:underline text-sm font-medium">Read the full Connector Guide →</a>
        </div>

    </div>
</div>
@endsection
