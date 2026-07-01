<?php
namespace App\Http\Controllers\Marketing;
use App\Http\Controllers\Controller;

class MarketingController extends Controller {
    public function home() { return view('marketing.home'); }
    public function pricing() { return view('marketing.pricing'); }
    public function features() { return view('marketing.features'); }
    public function about() { return view('marketing.about'); }
    public function contact() { return view('marketing.contact'); }
    public function help() { return view('marketing.help.index'); }
    public function helpTopic(string $topic) { return view("marketing.help.{$topic}", compact('topic')); }
    public function flows() { return view('marketing.flows'); }
    public function connectorGuide() { return view('marketing.connector-guide'); }
}
