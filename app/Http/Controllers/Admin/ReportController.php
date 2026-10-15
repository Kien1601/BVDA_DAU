<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /** Kỳ báo cáo dài nhất (ngày) */
    private const MAX_DAYS = 92;

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