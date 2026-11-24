<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryKind;
use App\Enums\InquiryStatus;
use App\Enums\LeadStatus;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Service;
use App\Services\Stats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Dashboard: real counts only — nothing is displayed when nothing has been measured. */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $props = [
            'content' => [
                'products' => Product::published()->count(),
                'services' => Service::where('is_published', true)->count(),
                'articles' => BlogArticle::published()->count(),
                'scheduled' => BlogArticle::where('status', PublishStatus::Published)->where('published_at', '>', now())->count(),
            ],
        ];

        if ($user->canDo('inquiries.view')) {
            $open = Inquiry::whereIn('status', [InquiryStatus::New, InquiryStatus::InProgress]);
            $props['inquiries'] = [
                'total' => Inquiry::where('status', '!=', InquiryStatus::Spam)->count(),
                'new' => Inquiry::where('status', InquiryStatus::New)->count(),
                'open' => (clone $open)->count(),
                'demo' => (clone $open)->where('kind', InquiryKind::Demo)->count(),
                'quote' => (clone $open)->where('kind', InquiryKind::Quote)->count(),
                'series' => Stats::inquirySeries(30),
                'latest' => Inquiry::where('status', '!=', InquiryStatus::Spam)->latest()->limit(6)->get()->map(fn ($i) => [
                    'id' => $i->id, 'name' => $i->full_name, 'organization' => $i->organization, 'kind' => $i->kind->label(), 'subject' => $i->subject,
                    'status' => $i->status->value, 'status_label' => $i->status->label(), 'created_at' => $i->created_at->toIso8601String(),
                ]),
            ];
        }
        if ($user->canDo('crm.view')) {
            $props['crm'] = [
                'pipeline' => collect(LeadStatus::cases())->map(fn ($s) => ['status' => $s->value, 'label' => $s->label(), 'count' => Lead::where('status', $s)->count()]),
                'followups' => Lead::whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->addDays(2))
                    ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost])->orderBy('next_follow_up_at')->limit(6)->get()
                    ->map(fn ($l) => ['id' => $l->id, 'name' => (string) $l, 'note' => $l->follow_up_note, 'at' => $l->next_follow_up_at->toIso8601String(), 'overdue' => $l->followUpOverdue()]),
                'recent' => Lead::latest()->limit(5)->get(['id', 'first_name', 'last_name'])->map(fn ($l) => ['id' => $l->id, 'name' => $l->full_name]),
            ];
        }
        if ($user->canDo('analytics.view')) {
            $series = Stats::traffic(30);
            $props['traffic'] = $series ? ['visitors' => array_sum(array_column($series, 'visitors')), 'views' => array_sum(array_column($series, 'views'))] : null;
        }

        return Inertia::render('Admin/Dashboard', $props);
    }
}
