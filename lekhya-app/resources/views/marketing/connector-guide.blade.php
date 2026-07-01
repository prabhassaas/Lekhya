@extends('layouts.marketing')
@section('title', 'Seedha Bill ↔ Lekhya Connector — API Guide')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-3xl font-bold text-gray-900 mb-4">Seedha Bill ↔ Lekhya Connector</h1>
    <p class="text-lg text-gray-600 mb-8">Two ways to connect Seedha Bill invoices to Lekhya — for same-account users and separate-account users.</p>

    <div class="grid md:grid-cols-2 gap-8 mb-12">
        <div class="bg-green-50 border-2 border-green-300 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-green-900 mb-3">Mode A — Same Prabhas Account</h2>
            <p class="text-green-800 text-sm mb-4">Both Seedha Bill and Lekhya under the same login. One-toggle setup. No token, no REST API.</p>
            <ul class="space-y-2 text-sm text-green-800">
                <li class="flex items-start space-x-2"><i class="fa fa-check text-green-600 mt-0.5"></i><span>Toggle "Auto-sync to Lekhya" in Seedha Bill settings</span></li>
                <li class="flex items-start space-x-2"><i class="fa fa-check text-green-600 mt-0.5"></i><span>Invoices sync via shared Supabase tables — no public API</span></li>
                <li class="flex items-start space-x-2"><i class="fa fa-check text-green-600 mt-0.5"></i><span>Cross-app bundle discount applied to your Lekhya subscription</span></li>
                <li class="flex items-start space-x-2"><i class="fa fa-check text-green-600 mt-0.5"></i><span>Solo plan covers this — no extra client seats needed</span></li>
            </ul>
        </div>
        <div class="bg-purple-50 border-2 border-purple-300 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-purple-900 mb-3">Mode B — Different Accounts (Token)</h2>
            <p class="text-purple-800 text-sm mb-4">Freelancer on Seedha Bill, accountant on Lekhya. Consent-based, revocable, audited.</p>
            <ul class="space-y-2 text-sm text-purple-800">
                <li class="flex items-start space-x-2"><i class="fa fa-check text-purple-600 mt-0.5"></i><span>Accountant generates a Client Connection Token in Lekhya</span></li>
                <li class="flex items-start space-x-2"><i class="fa fa-check text-purple-600 mt-0.5"></i><span>Freelancer pastes token in Seedha Bill → invoices flow to accountant</span></li>
                <li class="flex items-start space-x-2"><i class="fa fa-check text-purple-600 mt-0.5"></i><span>Each connection = 1 client seat on accountant's plan</span></li>
                <li class="flex items-start space-x-2"><i class="fa fa-check text-purple-600 mt-0.5"></i><span>Token is revocable — sync stops immediately on revocation</span></li>
            </ul>
        </div>
    </div>

    <h2 class="text-2xl font-bold text-gray-900 mb-6">Supabase ↔ MySQL Bridge (Mode A Technical Setup)</h2>
    <div class="space-y-6">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Architecture</h3>
            <div class="bg-gray-50 rounded-lg p-4 text-sm font-mono text-gray-700">
                Seedha Bill (Supabase/PostgreSQL)
                    → shared_invoices table (written by Seedha Bill)
                    → Lekhya reads via Supabase REST API + security-definer RPC
                    → ImportPipeline → MySQL (Lekhya DB)
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-gray-900 mb-3">Step 1 — Supabase Setup (Seedha Bill side)</h3>
            <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm overflow-x-auto">
                <p class="text-gray-500">-- Run this SQL in Supabase SQL editor (Seedha Bill's Supabase project)</p>
                <p class="mt-2">CREATE TABLE IF NOT EXISTS shared_invoices (</p>
                <p class="ml-4">id UUID PRIMARY KEY DEFAULT gen_random_uuid(),</p>
                <p class="ml-4">seedha_bill_tenant_id UUID NOT NULL,</p>
                <p class="ml-4">lekhya_tenant_ulid TEXT,  -- set when Mode A is enabled</p>
                <p class="ml-4">invoice_number TEXT NOT NULL,</p>
                <p class="ml-4">invoice_date DATE NOT NULL,</p>
                <p class="ml-4">customer_name TEXT NOT NULL,</p>
                <p class="ml-4">customer_gstin TEXT,</p>
                <p class="ml-4">customer_state_code TEXT,</p>
                <p class="ml-4">items JSONB,</p>
                <p class="ml-4">total_amount NUMERIC(20,4),</p>
                <p class="ml-4">sync_status TEXT DEFAULT 'pending',  -- pending | posted | locked</p>
                <p class="ml-4">locked_at TIMESTAMPTZ,</p>
                <p class="ml-4">created_at TIMESTAMPTZ DEFAULT now()</p>
                <p>);</p>
                <p class="mt-3 text-gray-500">-- Security-definer RPC that Lekhya calls</p>
                <p>CREATE OR REPLACE FUNCTION get_lekhya_pending_invoices(</p>
                <p class="ml-4">p_tenant_id UUID, p_since TIMESTAMPTZ DEFAULT NULL</p>
                <p>) RETURNS SETOF shared_invoices</p>
                <p>SECURITY DEFINER AS $$</p>
                <p class="ml-4">SELECT * FROM shared_invoices</p>
                <p class="ml-4">WHERE lekhya_tenant_ulid = p_tenant_id::TEXT</p>
                <p class="ml-4">AND sync_status = 'pending'</p>
                <p class="ml-4">AND (p_since IS NULL OR created_at > p_since);</p>
                <p>$$ LANGUAGE SQL;</p>
                <p class="mt-3 text-gray-500">-- Lock function (called after Lekhya posts invoice)</p>
                <p>CREATE OR REPLACE FUNCTION lock_invoice_for_lekhya(p_invoice_id UUID)</p>
                <p>RETURNS VOID SECURITY DEFINER AS $$</p>
                <p class="ml-4">UPDATE shared_invoices</p>
                <p class="ml-4">SET sync_status = 'locked', locked_at = now()</p>
                <p class="ml-4">WHERE id = p_invoice_id AND sync_status = 'pending';</p>
                <p>$$ LANGUAGE SQL;</p>
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-gray-900 mb-3">Step 2 — Configure Lekhya .env for Supabase</h3>
            <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm">
                <p class="text-gray-500"># Supabase credentials (from Seedha Bill's Supabase project)</p>
                <p>SUPABASE_URL=https://your-project.supabase.co</p>
                <p>SUPABASE_SERVICE_KEY=your-service-role-key  # service_role, not anon</p>
                <p class="mt-3 text-gray-500"># Seedha Bill REST API (Mode B)</p>
                <p>SEEDHA_BILL_BASE_URL=https://api.seedhabill.com/v1</p>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <h3 class="font-semibold text-amber-900 mb-2"><i class="fa fa-shield-halved mr-2"></i>Security Model</h3>
            <ul class="text-sm text-amber-800 space-y-1">
                <li>• Supabase RLS ensures Lekhya can only read invoices explicitly shared with it</li>
                <li>• The security-definer RPC runs with elevated privileges only for the defined function</li>
                <li>• Lekhya uses the Supabase service_role key only from the server — never exposed to browsers</li>
                <li>• Once an invoice is locked (posted), the RPC prevents any further writes to it</li>
            </ul>
        </div>
    </div>
</div>
@endsection
