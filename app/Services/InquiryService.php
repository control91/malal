<?php

namespace App\Services;

use App\Enums\ActivityKind;
use App\Enums\InquiryKind;
use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Models\Activity;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Business logic for inbound requests: persist, attach to a lead, notify. */
final class InquiryService
{
    public function register(Request $request, array $data, InquiryKind $kind): Inquiry
    {
        $inquiry = DB::transaction(function () use ($request, $data, $kind) {
            $inquiry = Inquiry::create($data + [
                'kind' => $kind,
                'consent' => true,
                'consent_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'source_url' => mb_substr((string) ($request->headers->get('referer') ?: $request->path()), 0, 255),
            ]);
            $lead = $this->attachLead($inquiry);
            Activity::create([
                'lead_id' => $lead->id, 'inquiry_id' => $inquiry->id, 'kind' => ActivityKind::Inquiry,
                'content' => "{$inquiry->kind->label()} reçue : « {$inquiry->subject} » ({$inquiry->reference}).",
            ]);

            return $inquiry;
        });

        DB::afterCommit(fn () => $this->notify($inquiry));

        return $inquiry;
    }

    public function attachLead(Inquiry $inquiry): Lead
    {
        $lead = Lead::whereRaw('lower(email) = ?', [strtolower($inquiry->email)])->latest()->first();
        if (! $lead) {
            $lead = Lead::create([
                'first_name' => $inquiry->first_name,
                'last_name' => $inquiry->last_name,
                'email' => strtolower($inquiry->email),
                'phone' => (string) $inquiry->phone,
                'organization' => (string) $inquiry->organization,
                'job_title' => (string) $inquiry->job_title,
                'country' => (string) $inquiry->country,
                'source' => LeadSource::Website,
                'service_category_id' => $inquiry->service_category_id ?: $inquiry->product?->service_category_id,
                'product_id' => $inquiry->product_id,
                'priority' => in_array($inquiry->kind, [InquiryKind::Quote, InquiryKind::Demo], true) ? LeadPriority::High : LeadPriority::Normal,
            ]);
        } else {
            foreach (['phone', 'organization', 'job_title', 'country'] as $field) {
                if (! $lead->{$field} && $inquiry->{$field}) {
                    $lead->{$field} = $inquiry->{$field};
                }
            }
            $lead->product_id ??= $inquiry->product_id;
            $lead->save();
        }
        $inquiry->update(['lead_id' => $lead->id]);

        return $lead;
    }

    private function notify(Inquiry $inquiry): void
    {
        $site = SiteSetting::current();
        Notifier::notifyCapability('inquiries.view', "{$inquiry->kind->label()} — {$inquiry->full_name}", $inquiry->subject, $inquiry->adminUrl(), 'inbox');
        $data = ['inquiry' => $inquiry, 'site' => $site];
        if ($site->notify_on_inquiry) {
            $recipients = $site->notificationRecipients() ?: [$site->email_sales ?: $site->email_contact];
            Notifier::mail($recipients, "[{$site->company_name}] {$inquiry->kind->label()} {$inquiry->reference}", 'inquiry_internal', $data, [$inquiry->email]);
        }
        if ($site->send_acknowledgement) {
            Notifier::mail([$inquiry->email], "{$site->company_name} — nous avons bien reçu votre demande", 'inquiry_ack', $data);
        }
    }
}
