<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\CouponCode;

class CouponCodeValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'couponValidation:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = date('Y-m-d');
        $coupon_codes = CouponCode::where('end_date', '<=', $today)->get();
        foreach($coupon_codes as $coupon_code) {
            $coupon_code->status = 'invalid';
            $coupon_code->save();
        }

        $this->info('Successfully changed statuses from coupon codes.');
        return 0;
    }
}
