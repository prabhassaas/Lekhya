@extends('layouts.marketing')
@section('title', 'Help Center — Lekhya')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Help Center</h1>
    <p class="text-lg text-gray-600 mb-12">Simple guides for everything in Lekhya. No jargon.</p>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach([
            ['getting-started','Getting Started','Login, setup company, add your first invoice','flag'],
            ['tally-migration','Tally Migration','Move from Tally ERP 9 or Tally Prime in minutes','file-import'],
            ['gst-api','GST API Integration','GSTIN validation, e-invoice, GSTR filing setup','landmark'],
            ['bank-reconciliation','Bank Reconciliation','Upload passbook, match transactions, close period','building-columns'],
            ['seedha-bill','Seedha Bill Connector','Connect Seedha Bill to auto-import invoices','plug'],
            ['local-llm','AI / LLM Setup','Set up local AI model (Ollama + Llama3) for Lekhya','robot'],
            ['double-entry','Double Entry Basics','How debits, credits, P&L, and Balance Sheet work','book'],
            ['pramaan','Lekhya Pramaan (CA)','UDIN, DSC, audit forms, compliance calendar','certificate'],
            ['hostinger-deploy','Deploy on Hostinger','Step-by-step PHP hosting setup guide','server'],
        ] as [$topic, $title, $desc, $icon])
        <a href="{{ route('marketing.help.topic', $topic) }}" class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-md transition hover:border-navy-300 group">
            <div class="w-10 h-10 bg-navy-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-navy-100 transition">
                <i class="fa fa-{{ $icon }} text-navy-600"></i>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ $title }}</h3>
            <p class="text-sm text-gray-500">{{ $desc }}</p>
        </a>
        @endforeach
    </div>
</div>
@endsection
