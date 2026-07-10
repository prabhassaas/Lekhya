<?php

namespace Database\Seeders;

use App\Models\AuditReport;
use App\Models\ComplianceCalendar;
use App\Models\DscCertificate;
use App\Models\Entitlement;
use App\Models\NoticeTracker;
use App\Models\Tenant;
use App\Models\UdinRegister;
use App\Models\User;
use App\Models\WorkingPaper;
use Illuminate\Database\Seeder;

/**
 * Enables the Lekhya Pramaan (CA edition) entitlement on the demo tenant and
 * seeds a realistic CA-practice dataset so every Pramaan screen shows genuine
 * data. Idempotent — safe to run on every deploy.
 */
class PramaanDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Strict: only ever touch the known demo tenant. Never fall back to
        // Tenant::first() — on production that could be a real account, and this
        // seeder writes fake CA data. If the demo tenant is absent, skip cleanly.
        $tenant = Tenant::where('slug', 'suvarna-textiles-demo')->first();
        if (! $tenant) {
            $this->command?->warn('Demo tenant not found — skipping Pramaan seed (no real accounts touched).');
            return;
        }

        // 1. Enable the Pramaan entitlement (idempotent).
        Entitlement::updateOrCreate(
            ['tenant_id' => $tenant->id, 'app' => 'lekhya', 'edition' => 'pramaan'],
            ['plan' => 'practice', 'client_seat_limit' => 25, 'is_active' => true],
        );

        $user = User::where('tenant_id', $tenant->id)->first();
        if (! $user) {
            $this->command?->warn('No user on tenant — skipping Pramaan data seed.');
            return;
        }

        // Don't duplicate data on re-run.
        if (UdinRegister::where('tenant_id', $tenant->id)->exists()) {
            $this->command?->info('Pramaan entitlement ensured; demo data already present.');
            return;
        }

        $membership = '234567';
        $clients = [
            'Sri Venkateswara Traders',
            'Pushpa Enterprises',
            'Deccan Steel Industries',
            'Coastal Exports LLP',
            'Namma Foods Pvt Ltd',
        ];

        // 2. DSC certificates.
        $dsc = DscCertificate::create([
            'tenant_id'        => $tenant->id,
            'holder_name'      => $user->name,
            'cn'               => strtoupper($user->name),
            'valid_from'       => now()->subMonths(8),
            'valid_to'         => now()->addMonths(16),
            'certificate_path' => 'metadata-only',
            'is_active'        => true,
        ]);
        DscCertificate::create([
            'tenant_id'        => $tenant->id,
            'holder_name'      => 'CA Suresh Menon',
            'cn'               => 'SURESH MENON',
            'valid_from'       => now()->subMonths(20),
            'valid_to'         => now()->addDays(18), // expiring soon
            'certificate_path' => 'metadata-only',
            'is_active'        => true,
        ]);

        // 3. UDINs.
        $udinTaxAudit = UdinRegister::create([
            'tenant_id'         => $tenant->id,
            'udin'              => '25' . $membership . 'AKTSRE1289',
            'membership_number' => $membership,
            'document_type'     => 'Tax Audit Report (3CD)',
            'document_date'     => now()->subDays(40),
            'client_name'       => $clients[0],
            'client_pan'        => 'AABCS7391K',
            'particulars'       => 'Turnover ₹4.82 Cr; Net profit ₹36.4 L; Tax audit u/s 44AB.',
            'status'            => 'generated',
            'generated_by'      => $user->id,
        ]);
        UdinRegister::create([
            'tenant_id'         => $tenant->id,
            'udin'              => '25' . $membership . 'BMQTWZ4471',
            'membership_number' => $membership,
            'document_type'     => 'Net Worth Certificate',
            'document_date'     => now()->subDays(12),
            'client_name'       => $clients[3],
            'client_pan'        => 'AAECC2210L',
            'particulars'       => 'Net worth ₹1.14 Cr certified for bank loan application.',
            'status'            => 'generated',
            'generated_by'      => $user->id,
        ]);
        UdinRegister::create([
            'tenant_id'         => $tenant->id,
            'udin'              => '25' . $membership . 'CPRXNL8830',
            'membership_number' => $membership,
            'document_type'     => 'Turnover Certificate',
            'document_date'     => now()->subDays(65),
            'client_name'       => $clients[1],
            'status'            => 'revoked',
            'revoked_at'        => now()->subDays(60),
            'generated_by'      => $user->id,
        ]);

        // 4. Audit reports across the workflow.
        AuditReport::create([
            'tenant_id'      => $tenant->id,
            'form_type'      => '3CD',
            'financial_year' => '2024-25',
            'status'         => 'signed',
            'preparer_id'    => $user->id,
            'reviewer_id'    => $user->id,
            'signer_id'      => $user->id,
            'udin_id'        => $udinTaxAudit->id,
            'dsc_id'         => $dsc->id,
            'signed_at'      => now()->subDays(40),
            'report_data'    => ['client_name' => $clients[0], 'client_pan' => 'AABCS7391K', 'observations' => 'No material weaknesses noted. All statutory dues paid within due dates.'],
        ]);
        $reviewReport = AuditReport::create([
            'tenant_id'      => $tenant->id,
            'form_type'      => '3CB',
            'financial_year' => '2024-25',
            'status'         => 'under_review',
            'preparer_id'    => $user->id,
            'reviewer_id'    => $user->id,
            'report_data'    => ['client_name' => $clients[1], 'client_pan' => 'AAFPP5521M', 'observations' => 'Pending: confirmation of sundry creditors above ₹5 L.'],
        ]);
        AuditReport::create([
            'tenant_id'      => $tenant->id,
            'form_type'      => 'Schedule_III',
            'financial_year' => '2024-25',
            'status'         => 'draft',
            'preparer_id'    => $user->id,
            'report_data'    => ['client_name' => $clients[2], 'client_pan' => 'AADCD9080P'],
        ]);

        // 5. Working papers linked to the report under review.
        foreach ([['Bank confirmations — HDFC & SBI', 'Bank Confirmations'], ['Fixed asset register reconciliation', 'Fixed Assets'], ['GST 2B vs books reconciliation', 'Statutory Dues']] as [$title, $cat]) {
            WorkingPaper::create([
                'tenant_id'       => $tenant->id,
                'audit_report_id' => $reviewReport->id,
                'title'           => $title,
                'category'        => $cat,
                'file_path'       => 'working-papers/sample.pdf',
                'file_name'       => str($title)->slug() . '.pdf',
                'mime_type'       => 'application/pdf',
                'uploaded_by'     => $user->id,
            ]);
        }

        // 6. Compliance calendar — mix of statuses incl. overdue and upcoming.
        $calendar = [
            [$clients[0], 'GST', 'Jun 2025', now()->addDays(4), 'pending'],
            [$clients[1], 'TDS', 'Q1 FY25-26', now()->addDays(2), 'in_progress'],
            [$clients[2], 'ITR', 'AY 2025-26', now()->addDays(25), 'pending'],
            [$clients[0], 'Audit', 'FY 2024-25', now()->subDays(6), 'pending'],   // overdue
            [$clients[3], 'ROC', 'FY 2024-25', now()->addDays(40), 'pending'],
            [$clients[4], 'GST', 'May 2025', now()->subDays(20), 'filed'],
            [$clients[1], 'AdvanceTax', 'Q1 FY25-26', now()->subDays(3), 'pending'], // overdue
            [$clients[4], 'PF/ESI', 'Jun 2025', now()->addDays(1), 'in_progress'],
        ];
        foreach ($calendar as [$client, $type, $period, $due, $status]) {
            ComplianceCalendar::create([
                'tenant_id'       => $tenant->id,
                'client_name'     => $client,
                'compliance_type' => $type,
                'period'          => $period,
                'due_date'        => $due,
                'status'          => $status,
                'assigned_to'     => $user->id,
                'completed_at'    => $status === 'filed' ? now()->subDays(18) : null,
            ]);
        }

        // 7. Notices.
        NoticeTracker::create([
            'tenant_id'         => $tenant->id,
            'client_name'       => $clients[0],
            'notice_type'       => 'GST Notice (ASMT-10)',
            'notice_number'     => 'ZA2906250012345',
            'notice_date'       => now()->subDays(15),
            'response_due_date' => now()->addDays(5),
            'authority'         => 'GST Department',
            'subject'           => 'Discrepancy in GSTR-3B vs GSTR-2B for FY 2024-25',
            'status'            => 'in_progress',
            'assigned_to'       => $user->id,
        ]);
        NoticeTracker::create([
            'tenant_id'         => $tenant->id,
            'client_name'       => $clients[2],
            'notice_type'       => 'IT Notice u/s 143(2)',
            'notice_number'     => 'CPC/2526/143/8871',
            'notice_date'       => now()->subDays(28),
            'response_due_date' => now()->subDays(2), // overdue
            'authority'         => 'Income Tax Department',
            'subject'           => 'Scrutiny assessment — mismatch in reported income',
            'status'            => 'received',
            'assigned_to'       => $user->id,
        ]);

        $this->command?->info("Pramaan (CA edition) enabled + seeded for '{$tenant->name}'.");
    }
}
