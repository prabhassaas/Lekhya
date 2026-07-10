<?php
namespace App\Http\Controllers\Pramaan;

use App\Http\Controllers\Controller;
use App\Models\AuditReport;
use App\Models\ComplianceCalendar;
use App\Models\NoticeTracker;
use App\Models\User;
use Illuminate\Http\Request;

class ComplianceCalendarController extends Controller
{
    private const TYPES = ['GST', 'TDS', 'ROC', 'AdvanceTax', 'Audit', 'ITR', 'PF/ESI'];

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $query = ComplianceCalendar::where('tenant_id', $tenantId)->with('assignee');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('type')) {
            $query->where('compliance_type', $request->get('type'));
        }

        $items = $query->orderBy('due_date')->paginate(50)->withQueryString();

        $all = ComplianceCalendar::where('tenant_id', $tenantId)->get();

        return view('pramaan.calendar.index', [
            'items'    => $items,
            'types'    => self::TYPES,
            'team'     => $this->team(),
            'filterStatus' => $request->get('status'),
            'filterType'   => $request->get('type'),
            'stats'    => [
                'overdue'   => $all->filter->isOverdue()->count(),
                'due_week'  => $all->where('status', '!=', 'filed')->filter(fn($i) => $i->due_date->betweenIncluded(now(), now()->addDays(7)))->count(),
                'pending'   => $all->whereIn('status', ['pending', 'in_progress'])->count(),
                'filed'     => $all->where('status', 'filed')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_name'     => 'required|string|max:255',
            'compliance_type' => 'required|in:' . implode(',', self::TYPES),
            'period'          => 'required|string|max:20',
            'due_date'        => 'required|date',
            'assigned_to'     => 'nullable|exists:users,id',
            'notes'           => 'nullable|string',
        ]);

        ComplianceCalendar::create(array_merge($data, [
            'tenant_id' => auth()->user()->tenant_id,
            'status'    => 'pending',
        ]));

        return back()->with('success', 'Compliance task added.');
    }

    public function update(Request $request, $id)
    {
        $item = ComplianceCalendar::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $data = $request->validate([
            'status'      => 'required|in:pending,in_progress,filed,overdue',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $item->update([
            'status'       => $data['status'],
            'assigned_to'  => $data['assigned_to'] ?? $item->assigned_to,
            'completed_at' => $data['status'] === 'filed' ? now() : null,
        ]);

        return back()->with('success', 'Task updated.');
    }

    public function clients()
    {
        $tenantId = auth()->user()->tenant_id;

        $compliance = ComplianceCalendar::where('tenant_id', $tenantId)->get()->groupBy('client_name');
        $reports    = AuditReport::where('tenant_id', $tenantId)->get()
            ->groupBy(fn($r) => $r->report_data['client_name'] ?? '—');
        $notices    = NoticeTracker::where('tenant_id', $tenantId)->get()->groupBy('client_name');

        $names = $compliance->keys()
            ->merge($reports->keys())
            ->merge($notices->keys())
            ->unique()->filter()->sort()->values();

        $clients = $names->map(function ($name) use ($compliance, $reports, $notices) {
            $items = $compliance->get($name, collect());
            return [
                'name'          => $name,
                'open'          => $items->whereIn('status', ['pending', 'in_progress'])->count(),
                'overdue'       => $items->filter->isOverdue()->count(),
                'total_tasks'   => $items->count(),
                'audit_reports' => $reports->get($name, collect())->count(),
                'notices'       => $notices->get($name, collect())->whereNotIn('status', ['closed'])->count(),
                'next_due'      => $items->where('status', '!=', 'filed')->sortBy('due_date')->first()?->due_date,
            ];
        });

        return view('pramaan.clients.index', [
            'clients' => $clients,
            'totals'  => [
                'clients'   => $clients->count(),
                'notices'   => NoticeTracker::where('tenant_id', $tenantId)->whereNotIn('status', ['closed'])->count(),
                'reports'   => AuditReport::where('tenant_id', $tenantId)->count(),
            ],
        ]);
    }

    private function team()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->orderBy('name')->get();
    }
}
