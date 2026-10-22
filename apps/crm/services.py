"""Business logic for inbound requests and the sales pipeline."""

import logging

from django.db import transaction
from django.utils import timezone

from apps.core.middleware import client_ip
from apps.core.models import SiteSettings
from apps.notifications.services import notify_capability, send_email

from .models import Activity, Inquiry, Lead

logger = logging.getLogger("apps.crm")


@transaction.atomic
def register_inquiry(request, form) -> Inquiry:
    """Persist a public request, attach it to a lead, then notify the team and the requester."""
    inquiry: Inquiry = form.save(commit=False)
    if "kind" not in form.fields:
        inquiry.kind = form.kind_value
    inquiry.consent = True
    inquiry.consent_at = timezone.now()
    inquiry.ip_address = client_ip(request) or None
    inquiry.user_agent = request.META.get("HTTP_USER_AGENT", "")[:255]
    inquiry.source_url = (request.META.get("HTTP_REFERER") or request.path)[:255]
    inquiry.save()

    lead = attach_lead(inquiry)
    Activity.objects.create(
        lead=lead, inquiry=inquiry, kind=Activity.Kind.INQUIRY,
        content=f"{inquiry.get_kind_display()} reçue : « {inquiry.subject} » ({inquiry.reference}).",
    )
    transaction.on_commit(lambda: _notify(inquiry))
    return inquiry


def attach_lead(inquiry: Inquiry) -> Lead:
    lead = Lead.objects.filter(email__iexact=inquiry.email).order_by("-created_at").first()
    if lead is None:
        lead = Lead.objects.create(
            first_name=inquiry.first_name,
            last_name=inquiry.last_name,
            email=inquiry.email.lower(),
            phone=inquiry.phone,
            organization=inquiry.organization,
            job_title=inquiry.job_title,
            country=inquiry.country,
            source=Lead.Source.WEBSITE,
            interest=inquiry.category or (inquiry.product.category if inquiry.product else None),
            product=inquiry.product,
            priority=Lead.Priority.HIGH if inquiry.kind in (Inquiry.Kind.QUOTE, Inquiry.Kind.DEMO) else Lead.Priority.NORMAL,
        )
    else:
        for field in ("phone", "organization", "job_title", "country"):
            if not getattr(lead, field) and getattr(inquiry, field):
                setattr(lead, field, getattr(inquiry, field))
        if inquiry.product and not lead.product:
            lead.product = inquiry.product
        lead.save()
    inquiry.lead = lead
    inquiry.save(update_fields=["lead"])
    return lead


def _notify(inquiry: Inquiry) -> None:
    site = SiteSettings.load()
    notify_capability(
        "inquiries.view",
        title=f"{inquiry.get_kind_display()} — {inquiry.full_name}",
        body=inquiry.subject,
        url=inquiry.get_dashboard_url(),
        icon="inbox",
    )
    context = {"inquiry": inquiry, "site": site}
    if site.notify_on_inquiry:
        recipients = site.notification_recipients or [site.email_sales or site.email_contact]
        send_email(f"[{site.company_name}] {inquiry.get_kind_display()} {inquiry.reference}", "emails/inquiry_internal", context, recipients, reply_to=[inquiry.email])
    if site.send_acknowledgement:
        send_email(f"{site.company_name} — nous avons bien reçu votre demande", "emails/inquiry_ack", context, [inquiry.email])


def log_activity(*, lead=None, inquiry=None, kind=Activity.Kind.NOTE, content="", author=None) -> Activity:
    return Activity.objects.create(lead=lead, inquiry=inquiry, kind=kind, content=content, author=author)
