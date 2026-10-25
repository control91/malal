<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityKind;
use App\Enums\AuditAction;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Mini CRM: lead file, pipeline, follow-ups, conversion. */
class LeadController extends Controller
{
    public function show(Request $request, Lead $lead): Response
    {
        $lead->load(['owner', 'interest', 'product', 'inquiries', 'activities.author', 'activities.inquiry']);

        return Inertia::render('Admin/Leads/Show', [
            'lead' => [
                'id' => $lead->id, 'full_name' => $lead->full_name, 'email' => $lead->email, 'phone' => $lead->phone, 'organization' => $lead->organization,
                'job_title' => $lead->job_title, 'country' => $lead->country, 'source' => $lead->source->label(), 'status' => $lead->status->value,
                'priority' => $lead->priority->value, 'priority_label' => $lead->priority->label(), 'owner' => $lead->owner ? (string) $lead->owner : null,
                'interest' => $lead->interest?->name, 'product' => $lead->product?->name, 'estimated_value' => $lead->estimated_value, 'currency' => $lead->currency,
                'next_follow_up_at' => $lead->next_follow_up_at?->format('Y-m-d\TH:i'), 'follow_up_note' => $lead->follow_up_note, 'overdue' => $lead->followUpOverdue(),
                'notes' => $lead->notes, 'lost_reason' => $lead->lost_reason, 'is_client' => $lead->is_client, 'converted_at' => $lead->converted_at?->toIso8601String(),
            ],
            'inquiries' => $lead->inquiries->map(fn ($i) => ['id' => $i->id, 'reference' => $i->reference, 'subject' => $i->subject, 'status' => $i->status->value, 'status_label' => $i->status->label(), 'created_at' => $i->created_at->toIso8601String()]),
            'activities' => self::timeline($lead->activities),
            'statuses' => LeadStatus::options(),
            'activityKinds' => self::manualKinds(),
            'canEdit' => $request->user()->canDo('crm.edit'),
        ]);
    }

    public function pipeline(Request $request): Response
    {
        $owner = (string) $request->query('responsable', '');
        $leads = Lead::with(['owner', 'interest'])->latest()
            ->when($owner === 'none', fn ($q) => $q->whereNull('owner_id'))
            ->when(ctype_digit($owner), fn ($q) => $q->where('owner_id', (int) $owner))->get();

        return Inertia::render('Admin/Leads/Pipeline', [
            'columns' => collect(LeadStatus::cases())->map(fn ($s) => [
                'status' => $s->value, 'label' => $s->label(),
                'leads' => $leads->where('status', $s)->values()->map(fn ($l) => [
                    'id' => $l->id, 'name' => $l->full_name, 'organization' => $l->organization ?: $l->email, 'priority' => $l->priority->value,
                    'interest' => $l->interest?->name, 'follow_up' => $l->next_follow_up_at?->toIso8601String(), 'overdue' => $l->followUpOverdue(),
                ]),
            ]),
            'owners' => User::where('is_active', true)->orderBy('first_name')->get()->map(fn ($u) => ['value' => $u->id, 'label' => (string) $u]),
            'currentOwner' => $owner,
            'canEdit' => $request->user()->canDo('crm.edit'),
        ]);
    }

    public function followups(Request $request): Response
    {
        $leads = Lead::with('owner')->whereNotNull('next_follow_up_at')->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost])
            ->when($request->boolean('mine'), fn ($q) => $q->where('owner_id', $request->user()->id))->orderBy('next_follow_up_at')->get()
            ->map(fn ($l) => ['id' => $l->id, 'name' => (string) $l, 'note' => $l->follow_up_note, 'owner' => $l->owner ? (string) $l->owner : null, 'at' => $l->next_follow_up_at->toIso8601String(), 'overdue' => $l->next_follow_up_at->isPast()]);

        return Inertia::render('Admin/Leads/Followups', [
            'overdue' => $leads->where('overdue', true)->values(),
            'upcoming' => $leads->where('overdue', false)->values(),
            'mine' => $request->boolean('mine'),
        ]);
    }

    public function status(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(LeadStatus::class)]]);
        $new = LeadStatus::from($data['status']);
        if ($new !== $lead->status) {
            $this->changeStatus($request, $lead, $new);
        }

        return back()->with('success', "Statut mis à jour : {$lead->status->label()}.");
    }

    public function note(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['kind' => ['required', Rule::in(array_column(self::manualKinds(), 'value'))], 'content' => ['required', 'string', 'max:5000']]);
        Activity::create($data + ['lead_id' => $lead->id, 'author_id' => $request->user()->id]);

        return back()->with('success', 'Interaction enregistrée.');
    }

    public function followup(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['next_follow_up_at' => ['nullable', 'date'], 'follow_up_note' => ['nullable', 'string', 'max:200']]);
        $lead->update(['next_follow_up_at' => $data['next_follow_up_at'] ?? null, 'follow_up_note' => $data['follow_up_note'] ?? '']);
        $when = $lead->next_follow_up_at?->format('d/m/Y H:i') ?? 'aucune';
        Activity::create(['lead_id' => $lead->id, 'kind' => ActivityKind::FollowUp, 'author_id' => $request->user()->id, 'content' => trim("Relance planifiée : {$when}. {$lead->follow_up_note}")]);

        return back()->with('success', 'Relance enregistrée.');
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        if (! $lead->is_client) {
            $lead->update(['is_client' => true, 'converted_at' => now()]);
            if ($lead->status !== LeadStatus::Won) {
                $this->changeStatus($request, $lead, LeadStatus::Won);
            }
            Audit::record(AuditAction::Convert, $lead);
            Activity::create(['lead_id' => $lead->id, 'kind' => ActivityKind::System, 'author_id' => $request->user()->id, 'content' => 'Prospect converti en client.']);
        }

        return back()->with('success', "{$lead->full_name} est désormais client.");
    }

    private function changeStatus(Request $request, Lead $lead, LeadStatus $status): void
    {
        $old = $lead->status->label();
        $lead->status = $status;
        if ($status === LeadStatus::Won && ! $lead->is_client) {
            $lead->is_client = true;
            $lead->converted_at = now();
        }
        $lead->save();
        Audit::record(AuditAction::Status, $lead, ['status' => [$old, $status->label()]]);
        Activity::create(['lead_id' => $lead->id, 'kind' => ActivityKind::Status, 'author_id' => $request->user()->id, 'content' => "Statut : {$old} → {$status->label()}"]);
    }

    public static function timeline(Collection $activities): array
    {
        return $activities->map(fn (Activity $a) => [
            'id' => $a->id, 'kind' => $a->kind->value, 'kind_label' => $a->kind->label(), 'content' => $a->content,
            'author' => $a->author ? (string) $a->author : 'Système', 'at' => $a->created_at->toIso8601String(),
            'inquiry' => $a->relationLoaded('inquiry') && $a->inquiry ? ['id' => $a->inquiry->id, 'reference' => $a->inquiry->reference] : null,
        ])->all();
    }

    public static function manualKinds(): array
    {
        return array_map(fn ($k) => ['value' => $k->value, 'label' => $k->label()], [ActivityKind::Note, ActivityKind::Call, ActivityKind::Email, ActivityKind::Meeting, ActivityKind::Demo, ActivityKind::FollowUp]);
    }
}
