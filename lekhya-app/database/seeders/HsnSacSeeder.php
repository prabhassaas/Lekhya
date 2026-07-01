<?php
namespace Database\Seeders;
use App\Models\HsnSacCode;
use Illuminate\Database\Seeder;

class HsnSacSeeder extends Seeder {
    public function run(): void {
        $codes = [
            ['code'=>'998311','type'=>'sac','description'=>'Management Consulting Services','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'998312','type'=>'sac','description'=>'Business Process Outsourcing Services','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'998313','type'=>'sac','description'=>'IT Design & Development','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'998314','type'=>'sac','description'=>'IT Infrastructure & Support','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'998315','type'=>'sac','description'=>'Accounting & Bookkeeping Services','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'998321','type'=>'sac','description'=>'Legal Documentation Services','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'996311','type'=>'sac','description'=>'Retail Trade Services','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'1001','type'=>'hsn','description'=>'Wheat and meslin','cgst_rate'=>0,'sgst_rate'=>0,'igst_rate'=>0],
            ['code'=>'8471','type'=>'hsn','description'=>'Computers and peripherals','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'8517','type'=>'hsn','description'=>'Mobile phones','cgst_rate'=>6,'sgst_rate'=>6,'igst_rate'=>12],
            ['code'=>'6402','type'=>'hsn','description'=>'Footwear','cgst_rate'=>9,'sgst_rate'=>9,'igst_rate'=>18],
            ['code'=>'8903','type'=>'hsn','description'=>'Yachts','cgst_rate'=>14,'sgst_rate'=>14,'igst_rate'=>28],
        ];
        foreach ($codes as $code) HsnSacCode::updateOrCreate(['code' => $code['code']], $code);
    }
}
