<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityKind;
use App\Enums\AuditAction;
use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function show(Request $request, Inquiry $inquiry): Response
    {
        $inquiry->load(['assignee', 'lead', 'product', 'category', 'plan', 'activities.author']);
        $site = SiteSetting::current();

        return Inertia::render('Admin/Inquiries/Show', [
            'inquiry' => [
                'id' => $inquiry->id, 'reference' => $inquiry->reference, 'kind' => $inquiry->kind->label(), 'status' => $inquiry->status->value,
                'status_label' => $inquiry->status->label(), 'full_name' => $inquiry->full_name, 'first_name' => $inquiry->first_name,
                'organization' => $inquiry->organization, 'job_title' => $inquiry->job_title, 'email' => $inquiry->email, 'phone' => $inquiry->phone,
                'country' => $inquiry->country, 'subject' => $inquiry->subject, 'message' => $inquiry->message, 'category' => $inquiry->category?->name,
                'product' => $inquiry->product?->name, 'plan' => $inquiry->plan ? (string) $inquiry->plan : null, 'budget' => $inquiry->budget?->label(),
                'timeline' => $inquiry->timeline?->label(), 'consent_at' => $inquiry->consent_at?->toIso8601String(), 'source_url' => $inquiry->source_url,
                'ip_address' => $inquiry->ip_address, 'assigned_to_id' => $inquiry->assigned_to_id, 'answered_at' => $inquiry->answered_at?->toIso8601String(),
                'created_at' => $inquiry->created_at->toIso8601String(),
                'lead' => $inquiry->lead ? ['id' => $inquiry->lead->id, 'status' => $inquiry->lead->status->label()] : null,
            ],
            'activities' => LeadController::timeline($inquiry->activities),
            'audit' => AuditLog::where('object_type', 'Inquiry')->where('object_id', (string) $inquiry->id)->latest('created_at')->limit(20)->get()
                ->map(fn ($a) => ['action' => $a->action->label(), 'actor' => $a->actor_label ?: 'Système', 'at' => $a->created_at->toIso8601String()]),
            'statuses' => InquiryStatus::options(),
            'staff' => self::staff(),
            'activityKinds' => LeadController::manualKinds(),
            'reply' => [
                'subject' => "Re: {$inquiry->subject} [{$inquiry->reference}]",
                'message' => "Bonjour {$inquiry->first_name},\n\nMerci pour votre demande.\n\n\n\nCordialement,\n{$request->user()->display_name}\n{$site->company_name}",
            ],
            'canEdit' => $request->user()->canDo('inquiries.edit'),
            'canCrm' => $request->user()->canDo('crm.view'),
        ]);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(InquiryStatus::class)],
            'assigned_to_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
        ]);
        $oldStatus = $inquiry->status->label();
        $oldAssignee = $inquiry->assignee ? (string) $inquiry->assignee : '—';
        $inquiry->update($data);

        if ($inquiry->wasChanged('status')) {
            Audit::record(AuditAction::Status, $inquiry, ['status' => [$oldStatus, $inquiry->status->label()]]);
            Activity::create(['inquiry_id' => $inquiry->id, 'lead_id' => $inquiry->lead_id, 'kind' => ActivityKind::Status, 'author_id' => $request->user()->id,
                'content' => "Statut de la demande : {$oldStatus} → {$inquiry->status->label()}"]);
        }
        if ($inquiry->wasChanged('assigned_to_id')) {
            $assignee = $inquiry->fresh('assignee')->assignee;
            Audit::record(AuditAction::Assign, $inquiry, ['assigned_to' => [$oldAssignee, $assignee ? (string) $assignee : '—']]);
            Activity::create(['inquiry_id' => $inquiry->id, 'lead_id' => $inquiry->lead_id, 'kind' => ActivityKind::Assignment, 'author_id' => $request->user()->id,
                'content' => 'Demande affectée à '.($assignee ? (string) $assignee : 'personne')]);
            if ($assignee && ! $assignee->is($request->user())) {
                AdminNotification::create(['user_id' => $assignee->id, 'title' => "Demande {$inquiry->reference} vous est affectée", 'body' => $inquiry->subject, 'url' => $inquiry->adminUrl(), 'icon' => 'user-check']);
            }
        }

        return back()->with('success', 'Demande mise à jour.');
    }

    public function note(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate(['kind' => ['required', Rule::in(array_column(LeadController::manualKinds(), 'value'))], 'content' => ['required', 'string', 'max:5000']]);
        Activity::create($data + ['inquiry_id' => $inquiry->id, 'lead_id' => $inquiry->lead_id, 'author_id' => $request->user()->id]);

        return back()->with('success', 'Commentaire interne ajouté.');
    }

    public function reply(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate(['subject' => ['required', 'string', 'max:200'], 'message' => ['required', 'string', 'max:10000'], 'mark_answered' => ['boolean']]);
        $site = SiteSetting::current();
        Notifier::mail([$inquiry->email], $data['subject'], 'inquiry_reply', ['body' => $data['message'], 'site' => $site, 'inquiry' => $inquiry],
            [$request->user()->email, $site->email_sales ?: $site->email_contact]);
        Activity::create(['inquiry_id' => $inquiry->id, 'lead_id' => $inquiry->lead_id, 'kind' => ActivityKind::Email, 'author_id' => $request->user()->id,
            'content' => "Email envoyé : « {$data['subject']} »\n\n{$data['message']}"]);
        Audit::record(AuditAction::Email, $inquiry, ['subject' => $data['subject'], 'to' => $inquiry->email]);
        if ($request->boolean('mark_answered')) {
            $inquiry->update(['status' => InquiryStatus::Answered, 'answered_at' => now(), 'assigned_to_id' => $inquiry->assigned_to_id ?: $request->user()->id]);
        }

        return back()->with('success', "Réponse envoyée à {$inquiry->email}.");
    }

    public static function staff(): array
    {
        return User::where('is_active', true)->orderBy('first_name')->get()->map(fn ($u) => ['value' => $u->id, 'label' => (string) $u])->all();
    }
}
