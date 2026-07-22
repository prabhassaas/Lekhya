@props([
    'type',            // report slug, e.g. 'day-book'
    'filters' => [],   // current query params to carry into PDF + send (from/to/as_of/party_id…)
    'email' => null,   // pre-fill recipient (e.g. the party's email)
    'phone' => null,   // party phone → enables WhatsApp
])
@php
    $canSend = auth()->user()?->hasAnyRole(['owner', 'accountant', 'ca']) ?? false;
    $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');
    $pdfUrl  = route('accounting.reports.pdf', $type) . (count($filters) ? '?' . http_build_query($filters) : '');
@endphp

<div class="flex items-center gap-2" x-data="{ shareOpen: false, channel: 'email' }">
    <a href="{{ $pdfUrl }}" class="px-4 py-1.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
        <i class="fa fa-file-pdf mr-1.5 text-red-500"></i>PDF
    </a>

    @if($canSend)
    <button type="button" @click="shareOpen = true" class="px-4 py-1.5 border border-navy-300 text-navy-700 text-sm font-medium rounded-lg hover:bg-navy-50">
        <i class="fa fa-paper-plane mr-1.5"></i>Send
    </button>

    {{-- Teleported to <body> so the POST form never nests inside a filter form. --}}
    <template x-teleport="body">
    <div x-show="shareOpen" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4" @click.self="shareOpen = false">
        <div class="bg-white rounded-2xl w-full max-w-md">
            <form method="POST" action="{{ route('accounting.reports.send', $type) }}">
                @csrf
                @foreach($filters as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach

                <div class="flex items-center justify-between px-6 pt-6 pb-1">
                    <h3 class="font-semibold text-gray-900"><i class="fa fa-paper-plane text-navy-500 mr-1.5"></i>Send report</h3>
                    <button type="button" @click="shareOpen = false" class="text-gray-400 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
                </div>
                <p class="px-6 text-sm text-gray-500 mb-4">The report is generated as a PDF and sent to your customer or vendor.</p>

                <div class="px-6 space-y-4">
                    @if($phone)
                    <div class="flex gap-2">
                        <button type="button" @click="channel = 'email'" :class="channel === 'email' ? 'bg-navy-600 text-white' : 'bg-gray-100 text-gray-600'" class="flex-1 px-3 py-1.5 rounded-lg text-sm font-medium"><i class="fa fa-envelope mr-1"></i>Email</button>
                        <button type="button" @click="channel = 'whatsapp'" :class="channel === 'whatsapp' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'" class="flex-1 px-3 py-1.5 rounded-lg text-sm font-medium"><i class="fa fa-whatsapp mr-1"></i>WhatsApp</button>
                    </div>
                    <input type="hidden" name="channel" :value="channel">
                    @else
                    <input type="hidden" name="channel" value="email">
                    @endif

                    <div x-show="channel === 'email'">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Recipient email</label>
                        <input type="email" name="recipient" value="{{ $email }}" placeholder="name@company.com"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-navy-500 focus:border-navy-500">
                    </div>

                    @if($phone)
                    <div x-show="channel === 'whatsapp'" x-cloak>
                        <label class="block text-xs font-medium text-gray-600 mb-1">WhatsApp number</label>
                        <input type="text" name="phone" value="{{ $phone }}" placeholder="9XXXXXXXXX"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-navy-500 focus:border-navy-500">
                        <p class="text-xs text-gray-400 mt-1">Sends a “report ready” message; the PDF itself is delivered by email.</p>
                    </div>
                    @endif

                    <div x-show="channel === 'email'">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Message <span class="text-gray-400">(optional)</span></label>
                        <textarea name="message" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none" placeholder="Please find your statement attached."></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-6 py-4 mt-2 border-t border-gray-100">
                    <button type="button" @click="shareOpen = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">Send</button>
                </div>
            </form>
        </div>
    </div>
    </template>
    @endif
</div>
