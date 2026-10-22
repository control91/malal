"""Back-office screens that go beyond plain CRUD."""

from datetime import timedelta

from django.contrib import messages
from django.contrib.auth import get_user_model
from django.core.paginator import Paginator
from django.db.models import Count, Q, Sum
from django.db.models.functions import TruncDate
from django.http import HttpResponse, JsonResponse
from django.shortcuts import get_object_or_404, redirect, render
from django.urls import reverse
from django.utils import timezone
from django.views.decorators.http import require_POST

from apps.accounts.roles import has_capability
from apps.analytics.models import DailyPageView, DailyVisitor
from apps.audit.models import AuditLog
from apps.audit.services import form_changes, record
from apps.blog.models import BlogArticle
from apps.cms.models import Page, PageSection
from apps.core.models import PublishStatus, SiteSettings
from apps.crm.models import Activity, Inquiry, Lead
from apps.crm.services import log_activity
from apps.notifications.models import Notification
from apps.notifications.services import send_email
from apps.products.models import Product
from apps.services.models import Service

from .forms import ActivityForm, FollowUpForm, InquiryUpdateForm, LeadStatusForm, ProfileForm, ReplyForm, SiteSettingsForm
from .permissions import capability_required, staff_required


def _daily_series(days: int):
    """Visitors / page views per day; returns None when nothing was ever measured."""
    start = timezone.localdate() - timedelta(days=days - 1)
    views = dict(DailyPageView.objects.filter(date__gte=start).values_list("date").annotate(total=Sum("views")))
    visitors = dict(DailyVisitor.objects.filter(date__gte=start).values_list("date").annotate(total=Count("id")))
    if not views and not DailyPageView.objects.exists():
        return None
    series = []
    for i in range(days):
        day = start + timedelta(days=i)
        series.append({"date": day, "views": views.get(day, 0), "visitors": visitors.get(day, 0)})
    peak = max((row["views"] for row in series), default=0) or 1
    for row in series:
        row["pct_views"] = round(row["views"] * 100 / peak)
        row["pct_visitors"] = round(row["visitors"] * 100 / peak)
    return series


def _inquiry_series(days: int):
    start = timezone.now() - timedelta(days=days - 1)
    counts = {
        row["day"]: row["n"]
        for row in Inquiry.objects.filter(created_at__gte=start).exclude(status=Inquiry.Status.SPAM)
        .annotate(day=TruncDate("created_at")).values("day").annotate(n=Count("id"))
    }
    today = timezone.localdate()
    series = [{"date": today - timedelta(days=days - 1 - i), "count": counts.get(today - timedelta(days=days - 1 - i), 0)} for i in range(days)]
    peak = max((r["count"] for r in series), default=0) or 1
    for row in series:
        row["pct"] = round(row["count"] * 100 / peak)
    return series


@staff_required
def home(request):
    user = request.user
    now = timezone.now()
    ctx = {"can": {cap: has_capability(user, cap) for cap in ("inquiries.view", "crm.view", "content.edit", "blog.edit", "catalog.edit", "analytics.view")}}
    if ctx["can"]["inquiries.view"]:
        open_qs = Inquiry.objects.filter(status__in=[Inquiry.Status.NEW, Inquiry.Status.IN_PROGRESS])
        ctx.update(
            inquiries_total=Inquiry.objects.exclude(status=Inquiry.Status.SPAM).count(),
            inquiries_new=Inquiry.objects.filter(status=Inquiry.Status.NEW).count(),
            inquiries_open=open_qs.count(),
            demo_requests=open_qs.filter(kind=Inquiry.Kind.DEMO).count(),
            quote_requests=open_qs.filter(kind=Inquiry.Kind.QUOTE).count(),
            latest_inquiries=Inquiry.objects.exclude(status=Inquiry.Status.SPAM).select_related("assigned_to")[:6],
            inquiry_series=_inquiry_series(30),
        )
    if ctx["can"]["crm.view"]:
        ctx.update(
            recent_leads=Lead.objects.select_related("owner")[:5],
            followups=Lead.objects.filter(next_follow_up_at__isnull=False, next_follow_up_at__lte=now + timedelta(days=2))
            .exclude(status__in=[Lead.Status.WON, Lead.Status.LOST]).order_by("next_follow_up_at")[:6],
            pipeline=[(s, label, Lead.objects.filter(status=s).count()) for s, label in Lead.Status.choices],
        )
    ctx.update(
        products_published=Product.objects.published().count(),
        services_published=Service.objects.filter(is_published=True).count(),
        articles_published=BlogArticle.objects.published().count(),
        articles_scheduled=BlogArticle.objects.filter(status=PublishStatus.PUBLISHED, published_at__gt=now).count(),
    )
    if ctx["can"]["analytics.view"]:
        series = _daily_series(30)
        ctx["traffic"] = series
        if series:
            ctx["visitors_30d"] = sum(r["visitors"] for r in series)
            ctx["views_30d"] = sum(r["views"] for r in series)
    return render(request, "dashboard/home.html", ctx)


# ---------------------------------------------------------------------------
# Inquiries
# ---------------------------------------------------------------------------
@capability_required("inquiries.view")
def inquiry_detail(request, pk):
    inquiry = get_object_or_404(Inquiry.objects.select_related("assigned_to", "lead", "product", "category", "plan"), pk=pk)
    can_edit = has_capability(request.user, "inquiries.edit")
    site = SiteSettings.load()
    reply_initial = {
        "subject": f"Re: {inquiry.subject} [{inquiry.reference}]",
        "message": f"Bonjour {inquiry.first_name},\n\nMerci pour votre demande.\n\n\n\nCordialement,\n{request.user.display_name}\n{site.company_name}",
    }
    update_form = InquiryUpdateForm(instance=inquiry)
    if not can_edit:
        for field in update_form.fields.values():
            field.disabled = True
    context = {
        "inquiry": inquiry,
        "update_form": update_form,
        "note_form": ActivityForm(),
        "reply_form": ReplyForm(initial=reply_initial),
        "activities": inquiry.activities.select_related("author"),
        "audit": AuditLog.objects.filter(object_type=Inquiry._meta.label, object_id=str(inquiry.pk))[:20],
        "can_edit": can_edit,
        "can_crm": has_capability(request.user, "crm.view"),
    }
    return render(request, "dashboard/crm/inquiry_detail.html", context)


@require_POST
@capability_required("inquiries.edit")
def inquiry_update(request, pk):
    inquiry = get_object_or_404(Inquiry, pk=pk)
    old_status, old_assignee = inquiry.get_status_display(), inquiry.assigned_to
    form = InquiryUpdateForm(request.POST, instance=inquiry)
    if form.is_valid():
        form.save()
        if "status" in form.changed_data:
            record(request, AuditLog.Action.STATUS, inquiry, changes={"status": [old_status, inquiry.get_status_display()]})
            log_activity(inquiry=inquiry, lead=inquiry.lead, kind=Activity.Kind.STATUS, author=request.user,
                         content=f"Statut de la demande : {old_status} → {inquiry.get_status_display()}")
        if "assigned_to" in form.changed_data:
            record(request, AuditLog.Action.ASSIGN, inquiry, changes={"assigned_to": [str(old_assignee or "—"), str(inquiry.assigned_to or "—")]})
            log_activity(inquiry=inquiry, lead=inquiry.lead, kind=Activity.Kind.ASSIGNMENT, author=request.user,
                         content=f"Demande affectée à {inquiry.assigned_to or 'personne'}")
            if inquiry.assigned_to and inquiry.assigned_to != request.user:
                Notification.objects.create(recipient=inquiry.assigned_to, title=f"Demande {inquiry.reference} vous est affectée",
                                            body=inquiry.subject, url=inquiry.get_dashboard_url(), icon="user-check")
        messages.success(request, "Demande mise à jour.")
    else:
        messages.error(request, "Mise à jour impossible.")
    return redirect(inquiry.get_dashboard_url())


@require_POST
@capability_required("inquiries.edit")
def inquiry_note(request, pk):
    inquiry = get_object_or_404(Inquiry, pk=pk)
    form = ActivityForm(request.POST)
    if form.is_valid():
        log_activity(inquiry=inquiry, lead=inquiry.lead, kind=form.cleaned_data["kind"], content=form.cleaned_data["content"], author=request.user)
        messages.success(request, "Commentaire interne ajouté.")
    else:
        messages.error(request, "Le commentaire est vide.")
    return redirect(inquiry.get_dashboard_url() + "#timeline")


@require_POST
@capability_required("inquiries.edit")
def inquiry_reply(request, pk):
    inquiry = get_object_or_404(Inquiry, pk=pk)
    form = ReplyForm(request.POST)
    if not form.is_valid():
        messages.error(request, "L'objet et le message sont obligatoires.")
        return redirect(inquiry.get_dashboard_url())
    site = SiteSettings.load()
    reply_to = [request.user.email, site.email_sales or site.email_contact]
    send_email(form.cleaned_data["subject"], "emails/inquiry_reply", {"body": form.cleaned_data["message"], "site": site, "inquiry": inquiry}, [inquiry.email], reply_to=reply_to)
    log_activity(inquiry=inquiry, lead=inquiry.lead, kind=Activity.Kind.EMAIL, author=request.user,
                 content=f"Email envoyé : « {form.cleaned_data['subject']} »\n\n{form.cleaned_data['message']}")
    record(request, AuditLog.Action.EMAIL, inquiry, changes={"subject": form.cleaned_data["subject"], "to": inquiry.email})
    if form.cleaned_data["mark_answered"]:
        inquiry.status = Inquiry.Status.ANSWERED
        inquiry.answered_at = timezone.now()
        if not inquiry.assigned_to:
            inquiry.assigned_to = request.user
        inquiry.save()
    messages.success(request, f"Réponse envoyée à {inquiry.email}.")
    return redirect(inquiry.get_dashboard_url() + "#timeline")


# ---------------------------------------------------------------------------
# Leads (mini CRM)
# ---------------------------------------------------------------------------
@capability_required("crm.view")
def lead_detail(request, pk):
    lead = get_object_or_404(Lead.objects.select_related("owner", "interest", "product"), pk=pk)
    context = {
        "lead": lead,
        "activities": lead.activities.select_related("author", "inquiry"),
        "inquiries": lead.inquiries.all(),
        "note_form": ActivityForm(),
        "followup_form": FollowUpForm(initial={"next_follow_up_at": lead.next_follow_up_at, "follow_up_note": lead.follow_up_note}),
        "statuses": Lead.Status.choices,
        "can_edit": has_capability(request.user, "crm.edit"),
    }
    return render(request, "dashboard/crm/lead_detail.html", context)


def _change_lead_status(request, lead: Lead, status: str) -> None:
    old = lead.get_status_display()
    lead.status = status
    if status == Lead.Status.WON and not lead.is_client:
        lead.is_client = True
        lead.converted_at = timezone.now()
    lead.save()
    record(request, AuditLog.Action.STATUS, lead, changes={"status": [old, lead.get_status_display()]})
    log_activity(lead=lead, kind=Activity.Kind.STATUS, author=request.user, content=f"Statut : {old} → {lead.get_status_display()}")


@require_POST
@capability_required("crm.edit")
def lead_status(request, pk):
    lead = get_object_or_404(Lead, pk=pk)
    form = LeadStatusForm(request.POST)
    if form.is_valid() and form.cleaned_data["status"] != lead.status:
        _change_lead_status(request, lead, form.cleaned_data["status"])
        messages.success(request, f"Statut mis à jour : {lead.get_status_display()}.")
    if request.headers.get("HX-Request"):
        return render(request, "dashboard/crm/_pipeline_card.html", {"lead": lead})
    return redirect(lead.get_dashboard_url())


@require_POST
@capability_required("crm.edit")
def lead_note(request, pk):
    lead = get_object_or_404(Lead, pk=pk)
    form = ActivityForm(request.POST)
    if form.is_valid():
        log_activity(lead=lead, kind=form.cleaned_data["kind"], content=form.cleaned_data["content"], author=request.user)
        messages.success(request, "Interaction enregistrée.")
    else:
        messages.error(request, "Le contenu est obligatoire.")
    return redirect(lead.get_dashboard_url() + "#timeline")


@require_POST
@capability_required("crm.edit")
def lead_followup(request, pk):
    lead = get_object_or_404(Lead, pk=pk)
    form = FollowUpForm(request.POST)
    if form.is_valid():
        lead.next_follow_up_at = form.cleaned_data["next_follow_up_at"]
        lead.follow_up_note = form.cleaned_data["follow_up_note"]
        lead.save(update_fields=["next_follow_up_at", "follow_up_note", "updated_at"])
        when = timezone.localtime(lead.next_follow_up_at).strftime("%d/%m/%Y %H:%M") if lead.next_follow_up_at else "aucune"
        log_activity(lead=lead, kind=Activity.Kind.FOLLOW_UP, author=request.user, content=f"Relance planifiée : {when}. {lead.follow_up_note}")
        messages.success(request, "Relance enregistrée.")
    else:
        messages.error(request, "Date de relance invalide.")
    return redirect(lead.get_dashboard_url())


@require_POST
@capability_required("crm.edit")
def lead_convert(request, pk):
    lead = get_object_or_404(Lead, pk=pk)
    if not lead.is_client:
        lead.is_client = True
        lead.converted_at = timezone.now()
        if lead.status != Lead.Status.WON:
            _change_lead_status(request, lead, Lead.Status.WON)
        else:
            lead.save()
        record(request, AuditLog.Action.CONVERT, lead)
        log_activity(lead=lead, kind=Activity.Kind.SYSTEM, author=request.user, content="Prospect converti en client.")
        messages.success(request, f"{lead.full_name} est désormais client.")
    return redirect(lead.get_dashboard_url())


@capability_required("crm.view")
def pipeline(request):
    leads = Lead.objects.select_related("owner", "interest")
    if owner := request.GET.get("responsable"):
        leads = leads.filter(owner_id=owner) if owner.isdigit() else leads.filter(owner__isnull=True)
    columns = [{"status": s, "label": label, "leads": [lead for lead in leads if lead.status == s]} for s, label in Lead.Status.choices]
    owners = get_user_model().objects.filter(is_staff=True, is_active=True)
    return render(request, "dashboard/crm/pipeline.html", {"columns": columns, "owners": owners, "can_edit": has_capability(request.user, "crm.edit"), "current_owner": request.GET.get("responsable", "")})


@capability_required("crm.view")
def followups(request):
    now = timezone.now()
    qs = Lead.objects.filter(next_follow_up_at__isnull=False).exclude(status__in=[Lead.Status.WON, Lead.Status.LOST]).select_related("owner").order_by("next_follow_up_at")
    if request.GET.get("mine"):
        qs = qs.filter(owner=request.user)
    return render(request, "dashboard/crm/followups.html", {
        "overdue": [lead for lead in qs if lead.next_follow_up_at < now],
        "upcoming": [lead for lead in qs if lead.next_follow_up_at >= now],
        "mine": bool(request.GET.get("mine")),
    })


# ---------------------------------------------------------------------------
# CMS helpers
# ---------------------------------------------------------------------------
@require_POST
@capability_required("content.edit")
def section_move(request, pk, direction):
    section = get_object_or_404(PageSection, pk=pk)
    siblings = list(section.page.sections.order_by("order", "pk"))
    index = siblings.index(section)
    swap = index - 1 if direction == "up" else index + 1
    if 0 <= swap < len(siblings):
        siblings[index], siblings[swap] = siblings[swap], siblings[index]
        for position, item in enumerate(siblings):
            if item.order != position * 10:
                PageSection.objects.filter(pk=item.pk).update(order=position * 10)
        record(request, AuditLog.Action.UPDATE, section.page, changes={"sections": "ordre modifié"})
    return redirect(reverse("dashboard:pages_update", args=[section.page_id]) + "#sections")


# ---------------------------------------------------------------------------
# Settings, profile, notifications, analytics
# ---------------------------------------------------------------------------
@capability_required("settings.view")
def site_settings(request):
    instance = SiteSettings.load()
    instance = SiteSettings.objects.get(pk=instance.pk)
    can_edit = has_capability(request.user, "settings.edit")
    if request.method == "POST":
        if not can_edit:
            return HttpResponse(status=403)
        form = SiteSettingsForm(request.POST, request.FILES, instance=instance)
        if form.is_valid():
            form.save()
            record(request, AuditLog.Action.SETTINGS, instance, changes=form_changes(form))
            messages.success(request, "Paramètres enregistrés.")
            return redirect("dashboard:settings")
        messages.error(request, "Le formulaire contient des erreurs.")
    else:
        form = SiteSettingsForm(instance=instance)
    if not can_edit:
        for field in form.fields.values():
            field.disabled = True
    groups = [
        ("Identité", "building-2", ["company_name", "legal_name", "tagline", "logo", "logo_dark", "favicon", "registration_number", "tax_number"]),
        ("Couleurs de marque", "palette", ["color_primary", "color_accent", "color_ink"]),
        ("Coordonnées", "map-pin", ["email_contact", "email_sales", "email_support", "phone", "whatsapp", "address", "city", "country", "opening_hours", "map_embed_url"]),
        ("Réseaux sociaux", "share-2", ["linkedin_url", "x_url", "facebook_url", "instagram_url", "youtube_url", "github_url"]),
        ("SEO par défaut", "search", ["meta_title", "meta_description", "og_image", "twitter_handle"]),
        ("Contenus transverses", "type", ["hosting_disclosure", "footer_text", "announcement", "announcement_url"]),
        ("Langues et notifications", "bell", ["enabled_languages", "notification_emails", "notify_on_inquiry", "send_acknowledgement"]),
    ]
    return render(request, "dashboard/settings.html", {"form": form, "groups": [(t, i, [form[n] for n in names]) for t, i, names in groups], "can_edit": can_edit})


@staff_required
def profile(request):
    if request.method == "POST":
        form = ProfileForm(request.POST, request.FILES, instance=request.user)
        if form.is_valid():
            form.save()
            messages.success(request, "Profil mis à jour.")
            return redirect("dashboard:profile")
    else:
        form = ProfileForm(instance=request.user)
    return render(request, "dashboard/account/profile.html", {"form": form})


@staff_required
def notifications(request):
    qs = request.user.notifications.all()
    page = Paginator(qs, 30).get_page(request.GET.get("page"))
    return render(request, "dashboard/notifications.html", {"page_obj": page})


@staff_required
def notifications_menu(request):
    items = request.user.notifications.all()[:8]
    return render(request, "dashboard/includes/notifications_menu.html", {"items": items})


@require_POST
@staff_required
def notification_read(request, pk=None):
    qs = request.user.notifications.filter(read_at__isnull=True)
    if pk:
        notification = get_object_or_404(request.user.notifications, pk=pk)
        if not notification.read_at:
            notification.read_at = timezone.now()
            notification.save(update_fields=["read_at"])
        if notification.url and notification.url.startswith("/"):
            return redirect(notification.url)
    else:
        qs.update(read_at=timezone.now())
    return redirect(request.POST.get("next") if (request.POST.get("next") or "").startswith("/") else "dashboard:notifications")


@capability_required("analytics.view")
def analytics(request):
    days = 90 if request.GET.get("periode") == "90" else 30
    series = _daily_series(days)
    start = timezone.localdate() - timedelta(days=days - 1)
    top_pages = DailyPageView.objects.filter(date__gte=start).values("path").annotate(total=Sum("views")).order_by("-total")[:15]
    inquiries = Inquiry.objects.filter(created_at__date__gte=start).exclude(status=Inquiry.Status.SPAM)
    by_kind = inquiries.values("kind").annotate(n=Count("id")).order_by("-n")
    kind_labels = dict(Inquiry.Kind.choices)
    context = {
        "days": days,
        "series": series,
        "top_pages": top_pages,
        "visitors": sum(r["visitors"] for r in series) if series else 0,
        "views": sum(r["views"] for r in series) if series else 0,
        "inquiries_count": inquiries.count(),
        "by_kind": [(kind_labels.get(row["kind"], row["kind"]), row["n"]) for row in by_kind],
        "won": Lead.objects.filter(converted_at__date__gte=start).count(),
    }
    return render(request, "dashboard/analytics.html", context)


@staff_required
def search(request):
    """Global search across the entities the user may see."""
    q = request.GET.get("q", "").strip()[:100]
    results = []
    if len(q) >= 2:
        user = request.user
        if has_capability(user, "inquiries.view"):
            for obj in Inquiry.objects.filter(Q(reference__icontains=q) | Q(email__icontains=q) | Q(last_name__icontains=q) | Q(organization__icontains=q) | Q(subject__icontains=q))[:5]:
                results.append(("Demande", str(obj), obj.subject, obj.get_dashboard_url(), "inbox"))
        if has_capability(user, "crm.view"):
            for obj in Lead.objects.filter(Q(email__icontains=q) | Q(last_name__icontains=q) | Q(first_name__icontains=q) | Q(organization__icontains=q))[:5]:
                results.append(("Prospect", str(obj), obj.get_status_display(), obj.get_dashboard_url(), "contact"))
        if has_capability(user, "blog.view"):
            for obj in BlogArticle.objects.filter(title__icontains=q)[:5]:
                results.append(("Article", obj.title, obj.display_status, reverse("dashboard:articles_update", args=[obj.pk]), "newspaper"))
        if has_capability(user, "catalog.view"):
            for obj in Product.objects.filter(name__icontains=q)[:5]:
                results.append(("Solution", obj.name, obj.get_status_display(), reverse("dashboard:solutions_update", args=[obj.pk]), "package"))
        if has_capability(user, "content.view"):
            for obj in Page.objects.filter(title__icontains=q)[:5]:
                results.append(("Page", obj.title, obj.get_status_display(), reverse("dashboard:pages_update", args=[obj.pk]), "panels-top-left"))
    if request.headers.get("HX-Request"):
        return render(request, "dashboard/includes/search_results.html", {"results": results, "q": q})
    return render(request, "dashboard/search.html", {"results": results, "q": q})


@staff_required
def keepalive(request):
    return JsonResponse({"ok": True, "unread": request.user.notifications.filter(read_at__isnull=True).count()})
