<?php
namespace App\Http\Controllers\Pramaan;
use App\Http\Controllers\Controller;
use App\Models\ComplianceCalendar;
use Illuminate\Http\Request;
class ComplianceCalendarController extends Controller {
    public function index() { return view('pramaan.calendar.index', ['items' => ComplianceCalendar::where('tenant_id', auth()->user()->tenant_id)->orderBy('due_date')->paginate(50)]); }
    public function clients() { return view('pramaan.clients.index'); }
}
