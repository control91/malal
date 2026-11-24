<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\DailyPageView;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Services\Stats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $days = $request->query('periode') === '90' ? 90 : 30;
        $start = today()->subDays($days - 1);
        $series = Stats::traffic($days);
        $inquiries = Inquiry::where('created_at', '>=', $start)->where('status', '!=', InquiryStatus::Spam);

        return Inertia::render('Admin/Analytics', [
            'days' => $days,
            'series' => $series,
            'totals' => $series ? ['visitors' => array_sum(array_column($series, 'visitors')), 'views' => array_sum(array_column($series, 'views'))] : null,
            'topPages' => DailyPageView::where('date', '>=', $start)->groupBy('path')->selectRaw('path, sum(views) as total')->orderByDesc('total')->limit(15)->get(),
            'inquiries' => (clone $inquiries)->count(),
            'byKind' => (clone $inquiries)->get(['kind'])->countBy(fn ($i) => $i->kind->label())->sortDesc()->map(fn ($n, $label) => ['label' => $label, 'count' => $n])->values(),
            'won' => Lead::where('converted_at', '>=', $start)->count(),
        ]);
    }
}
