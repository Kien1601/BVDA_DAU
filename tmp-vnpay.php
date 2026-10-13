<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\RentalOrder;
use App\Services\PaymentService;
use App\Services\Payments\VnpayGateway;

if (($argv[1] ?? '') === 'check') {
    // Xem khoản thanh toán mới nhất
    print_r(Payment::latest('id')->first()?->only('id', 'status', 'amount', 'provider_txn_id'));
    exit;
}

$order = RentalOrder::where('status', 'pending')->latest('id')->first();
if (! $order) {
    exit("Khong co don pending nao. Hay dat mot don moi bang tai khoan khach.\n");
}

$payment = app(PaymentService::class)->startOnlineDeposit($order, PaymentMethod::Vnpay);
echo app(VnpayGateway::class)->createPaymentUrl($payment, '127.0.0.1'), PHP_EOL;