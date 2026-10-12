<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'rental:expire-pending';

    protected $description = 'Hủy các đơn chờ duyệt đã quá thời hạn giữ chỗ';

    public function handle(BookingService $booking): int
    {
        $count = $booking->expirePending();
        $this->info("Đã hủy {$count} đơn quá hạn giữ chỗ.");

        return self::SUCCESS;
    }
}