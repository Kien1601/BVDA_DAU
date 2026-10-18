<?php

namespace App\Console\Commands;

use App\Services\OrderAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rental:scan-alerts')]
#[Description('Quét đơn sắp giao, sắp trả, quá hạn và báo cho nhân viên')]
class ScanOrderAlerts extends Command
{
    public function handle(OrderAlertService $alerts): int
    {
        $this->info('Đã gửi '.$alerts->scan().' thông báo.');

        return self::SUCCESS;
    }
}