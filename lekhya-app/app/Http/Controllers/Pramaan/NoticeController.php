<?php
namespace App\Http\Controllers\Pramaan;

use App\Http\Controllers\Controller;
use App\Models\NoticeTracker;
use App\Models\User;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    private const STATUSES = ['received', 'in_progress', 'replied', 'closed', 'appealed'];

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $query = NoticeTracker::where('tenant_id', $tenantId)->with('assignee');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $all = NoticeTracker::where('tenant_id', $tenantId)->get();

        return view('pramaan.notices.index', [
            'notices'  => $query->orderByRaw('response_due_date is null, response_due_date asc')->paginate(30)->withQueryString(),
            'statuses' => self::STATUSES,
            'team'     => $this->team(),
            'filterStatus' => $request->get('status'),
            'stats'    => [
                'open'    => $all->whereNotIn('status', ['closed'])->count(),
                'overdue' => $all->filter->isOverdue()->count(),
                'replied' => $all->where('status', 'replied')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_name'       => 'required|string|max:255',
            'notice_type'       => 'required|string|max:100',
            'notice_number'     => 'nullable|string|max:50',
            'notice_date'       => 'required|date',
            'response_due_date' => 'nullable|date',
            'authority'         => 'nullable|string|max:100',
            'subject'           => 'nullable|string',
            'assigned_to'       => 'nullable|exists:users,id',
        ]);

        NoticeTracker::create(array_merge($data, [
            'tenant_id' => auth()->user()->tenant_id,
            'status'    => 'received',
        ]));

        return back()->with('success', 'Notice logged.');
    }

    public function update(Request $request, $id)
    {
        $notice = NoticeTracker::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $data = $request->validate([
            'status'      => 'required|in:' . implode(',', self::STATUSES),
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $notice->update([
            'status'      => $data['status'],
            'assigned_to' => $data['assigned_to'] ?? $notice->assigned_to,
        ]);

        return back()->with('success', 'Notice updated.');
    }

    public function destroy($id)
    {
        NoticeTracker::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();

        return back()->with('success', 'Notice removed.');
    }

    private function team()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->orderBy('name')->get();
    }
}
