<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller {
    public function index() {
        $tenantId = auth()->user()->tenant_id;
        $accounts = Account::where('tenant_id', $tenantId)->with('parent')->orderBy('code')->get();
        return view('accounting.accounts.index', compact('accounts'));
    }
    public function create() {
        $tenantId = auth()->user()->tenant_id;
        $parents = Account::where('tenant_id', $tenantId)->where('is_ledger', false)->get();
        return view('accounting.accounts.form', compact('parents'));
    }
    public function store(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate(['code'=>'required','name'=>'required','type'=>'required|in:asset,liability,equity,revenue,expense','sub_type'=>'nullable','parent_id'=>'nullable|exists:accounts,id','is_ledger'=>'boolean']);
        Account::create(array_merge($validated, ['tenant_id' => $tenantId, 'is_active' => true]));
        return redirect()->route('accounting.accounts.index')->with('success', 'Account created.');
    }
    public function show(Account $account) { return view('accounting.accounts.show', compact('account')); }
    public function edit(Account $account) {
        $parents = Account::where('tenant_id', auth()->user()->tenant_id)->where('is_ledger', false)->get();
        return view('accounting.accounts.form', compact('account', 'parents'));
    }
    public function update(Request $request, Account $account) {
        if ($account->is_system) return back()->with('error', 'System accounts cannot be modified.');
        $account->update($request->only(['name','sub_type','parent_id','description']));
        return redirect()->route('accounting.accounts.index')->with('success', 'Account updated.');
    }
    public function destroy(Account $account) {
        if ($account->is_system) return back()->with('error', 'System accounts cannot be deleted.');
        $account->delete();
        return redirect()->route('accounting.accounts.index')->with('success', 'Account deleted.');
    }
    public function ledger(Account $account, Request $request) {
        $from = $request->get('from', date('Y-04-01'));
        $to = $request->get('to', date('Y-m-d'));
        $lines = $account->journalLines()->with(['journal'])->whereHas('journal', fn($q) => $q->where('date','>=', $from)->where('date','<=',$to)->where('is_posted',true))->orderBy('created_at')->get();
        return view('accounting.accounts.ledger', compact('account','lines','from','to'));
    }
}
