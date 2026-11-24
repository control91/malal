<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Models\DailyPageView;
use App\Models\DailyVisitor;
use App\Models\Inquiry;
use Illuminate\Support\Facades\DB;

final class Stats
{
    /** Views/visitors per day, or null when nothing was ever measured. */
    public static function traffic(int $days): ?array
    {
        if (! DailyPageView::query()->exists()) {
            return null;
        }
        $start = today()->subDays($days - 1);
        $views = DailyPageView::where('date', '>=', $start)->groupBy('date')->selectRaw('date, sum(views) as total')->pluck('total', 'date');
        $visitors = DailyVisitor::where('date', '>=', $start)->groupBy('date')->selectRaw('date, count(*) as total')->pluck('total', 'date');
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $series[] = ['date' => $day, 'views' => (int) ($views[$day] ?? $views[$day.' 00:00:00'] ?? 0), 'visitors' => (int) ($visitors[$day] ?? $visitors[$day.' 00:00:00'] ?? 0)];
        }

        return $series;
    }

    public static function inquirySeries(int $days): array
    {
        $start = today()->subDays($days - 1);
        $counts = Inquiry::where('created_at', '>=', $start)->where('status', '!=', InquiryStatus::Spam)
            ->groupBy(DB::raw('date(created_at)'))->selectRaw('date(created_at) as day, count(*) as total')->pluck('total', 'day');
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $series[] = ['date' => $day, 'count' => (int) ($counts[$day] ?? 0)];
        }

        return $series;
    }
}
