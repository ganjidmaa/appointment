<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Settings;
use Illuminate\Support\Facades\Log;


class QpayTokenRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:qpay-token-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://quickqr.qpay.mn',
        ]);
        $settings = Settings::find(1);
        $response = $client->request('POST', '/v2/auth/token', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'auth' => ['UBISOL', 'LScvBqcF'],
            'body' => json_encode(['terminal_id' => "99990001"])
        ]);
        $response = json_decode($response->getBody());
        $settings->qpay_token = $response->access_token;
        $settings->save();
    }
}
