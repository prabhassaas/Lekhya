<?php
namespace Database\Seeders;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder {
    public function run(): void {
        $plans = [
            ['slug'=>'solo','name'=>'Lekhya Solo','app'=>'lekhya','edition'=>'standard','tier'=>'solo','client_seat_limit'=>1,'user_seat_limit'=>2,'monthly_price'=>499,'annual_price'=>4999,'features'=>['journal','invoices','reports','gst']],
            ['slug'=>'practice','name'=>'Lekhya Practice','app'=>'lekhya','edition'=>'standard','tier'=>'practice','client_seat_limit'=>10,'user_seat_limit'=>5,'monthly_price'=>1299,'annual_price'=>12999,'features'=>['journal','invoices','reports','gst','connector','ai']],
            ['slug'=>'firm','name'=>'Lekhya Firm','app'=>'lekhya','edition'=>'standard','tier'=>'firm','client_seat_limit'=>30,'user_seat_limit'=>20,'monthly_price'=>2999,'annual_price'=>29999,'features'=>['journal','invoices','reports','gst','connector','ai','bulk_gstr']],
            ['slug'=>'pramaan','name'=>'Lekhya Pramaan','app'=>'lekhya','edition'=>'pramaan','tier'=>'firm','client_seat_limit'=>100,'user_seat_limit'=>50,'monthly_price'=>4999,'annual_price'=>49999,'features'=>['all','udin','dsc','audit_forms','compliance_calendar','white_label']],
        ];
        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
