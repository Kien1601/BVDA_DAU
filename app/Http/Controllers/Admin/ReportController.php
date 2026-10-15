<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Enums\OrderStatus;

class ReportController extends Controller
{
    /** Kỳ báo cáo dài nhất (ngày) */
    private const MAX_DAYS = 92;
        /** Dấu phân cách cột. Excel mở ra dồn hết vào 1 cột thì đổi thành ';' */
    private const CSV_SEPARATOR = ',';
    public function __construct(private ReportService $reports)
    {
    }

    /** C8 - Báo cáo doanh thu, lượt thuê, xe thuê nhiều */
    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        return view('admin.reports.reportIndex', [
            'from' => $from,
            'to' => $to,
            'revenue' => $this->reports->revenue($from, $to),
            'byDay' => $this->reports->revenueByDay($from, $to),
            'orders' => $this->reports->orderStats($from, $to),
            'topVehicles' => $this->reports->topVehicles($from, $to),
        ]);
    }
        /** C8 - Xuất báo cáo cùng kỳ ra CSV */
    public function export(Request $request)
    {
        [$from, $to] = $this->period($request);

        $revenue = $this->reports->revenue($from, $to);
        $byDay = $this->reports->revenueByDay($from, $to);
        $orders = $this->reports->orderStats($from, $to);
        $topVehicles = $this->reports->topVehicles($from, $to);

        $filename = 'bao-cao_'.$from->format('Ymd').'_'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($from, $to, $revenue, $byDay, $orders, $topVehicles) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel nhận đúng UTF-8
            $row = fn (array $cols) => fputcsv($out, array_map([$this, 'csvCell'], $cols), self::CSV_SEPARATOR, '"', '');

            $row(['BÁO CÁO RENTAL GPS']);
            $row(['Từ ngày', $from->format('d/m/Y'), 'Đến ngày', $to->format('d/m/Y')]);
            $row(['Xuất lúc', now()->format('H:i d/m/Y')]);
            $row(['']);

            $row(['TỔNG HỢP']);
            $row(['Doanh thu thực thu (đ)', $revenue]);
            $row(['Đơn mới', $orders['newOrders']]);
            $row(['Lượt thuê (giao xe)', $orders['rentals']]);
            $row(['']);

            $row(['DOANH THU THEO NGÀY']);
            $row(['Ngày', 'Doanh thu (đ)']);
            foreach ($byDay as $day => $amount) {
                $row([Carbon::parse($day)->format('d/m/Y'), $amount]);
            }
            $row(['']);

            $row(['ĐƠN TẠO TRONG KỲ']);
            $row(['Trạng thái', 'Số đơn']);
            foreach (OrderStatus::cases() as $case) {
                $row([$case->label(), $orders['byStatus'][$case->value] ?? 0]);
            }
            $row(['']);

            $row(['XE THUÊ NHIỀU NHẤT']);
            $row(['Hạng', 'Xe', 'Biển số', 'Loại', 'Lượt thuê', 'Tổng tiền đơn (đ)']);
            foreach ($topVehicles as $i => $vehicle) {
                $row([$i + 1, $vehicle->name, $vehicle->license_plate, $vehicle->type, (int) $vehicle->rentals, (int) $vehicle->amount]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Chặn chữ bắt đầu bằng = + - @ để Excel không hiểu nhầm là công thức */
    private function csvCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
    /**
     * Đọc kỳ từ ?from=&to= (dạng Y-m-d). Thiếu thì lấy 30 ngày gần nhất.
     * Nhập ngược thì đổi chỗ, dài quá thì cắt bớt, không báo lỗi.
     */
    private function period(Request $request): array
    {
        $to = $this->parseDate($request->query('to')) ?? today();
        $from = $this->parseDate($request->query('from')) ?? $to->copy()->subDays(29);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > self::MAX_DAYS - 1) {
            $from = $to->copy()->subDays(self::MAX_DAYS - 1);
        }

        return [$from, $to];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}