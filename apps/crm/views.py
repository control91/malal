import logging

from django.contrib import messages
from django.shortcuts import get_object_or_404, redirect, render
from django.urls import reverse
from django.utils import timezone
from django.views.decorators.http import require_POST

from apps.core.antispam import rate_limited
from apps.core.models import SiteSettings
from apps.core.seo import build_seo
from apps.notifications.services import send_email
from apps.pricing.models import PricingPlan
from apps.products.models import Product
from apps.services.models import ServiceCategory

from .forms import ContactForm, DemoForm, NewsletterForm, QuoteForm
from .models import NewsletterSubscriber
from .services import register_inquiry

logger = logging.getLogger("apps.crm")

RATE_LIMIT_MESSAGE = "Trop de demandes envoyées depuis votre connexion. Merci de réessayer dans quelques minutes."


def _handle(request, form_class, *, template, scope, seo, initial=None, extra=None):
    if request.method == "POST":
        form = form_class(request.POST)
        if rate_limited(request, scope):
            form.add_error(None, RATE_LIMIT_MESSAGE)
        elif form.is_valid():
            inquiry = register_inquiry(request, form)
            request.session["last_inquiry"] = inquiry.reference
            return redirect(reverse("crm:thanks"))
        elif form.spam_detected:
            logger.info("spam submission rejected on %s", scope)
    else:
        form = form_class(initial=initial or {})
    return render(request, template, {"form": form, "seo": seo, **(extra or {})}, status=400 if request.method == "POST" else 200)


def contact(request):
    initial = {}
    if kind := request.GET.get("type"):
        initial["kind"] = kind
    seo = build_seo(request, title="Contact", description="Parlez de votre projet Cloud, SaaS ou IA avec un expert.")
    return _handle(request, ContactForm, template="public/contact.html", scope="contact", seo=seo, initial=initial, extra={"site": SiteSettings.load()})


def quote(request):
    initial = {}
    if plan_id := request.GET.get("offre"):
        plan = PricingPlan.objects.filter(pk=plan_id if plan_id.isdigit() else 0, is_published=True).select_related("group__product", "group__category").first()
        if plan:
            initial.update(plan=plan.pk, subject=f"Devis — {plan}", product=plan.group.product_id, category=plan.group.category_id)
    if slug := request.GET.get("solution"):
        if product := Product.objects.published().filter(slug=slug).first():
            initial.update(product=product.pk, category=product.category_id, subject=f"Devis — {product.name}")
    if slug := request.GET.get("domaine"):
        if category := ServiceCategory.objects.filter(slug=slug, is_published=True).first():
            initial.setdefault("category", category.pk)
            initial.setdefault("subject", f"Projet {category.name}")
    seo = build_seo(request, title="Demander un devis", description="Décrivez votre besoin : nous revenons vers vous avec une proposition adaptée.")
    return _handle(request, QuoteForm, template="public/quote.html", scope="quote", seo=seo, initial=initial)


def demo(request):
    initial = {}
    if slug := request.GET.get("solution"):
        if product := Product.objects.published().filter(slug=slug, demo_enabled=True).first():
            initial["product"] = product.pk
    seo = build_seo(request, title="Demander une démonstration", description="Planifiez une démonstration personnalisée de nos solutions.")
    return _handle(request, DemoForm, template="public/demo.html", scope="demo", seo=seo, initial=initial)


def thanks(request):
    reference = request.session.pop("last_inquiry", None)
    if not reference:
        return redirect("cms:home")
    return render(request, "public/thanks.html", {"reference": reference, "seo": build_seo(request, title="Merci", noindex=True)})


@require_POST
def newsletter_subscribe(request):
    form = NewsletterForm(request.POST)
    is_htmx = bool(request.headers.get("HX-Request"))
    ok = False
    if rate_limited(request, "newsletter"):
        message = RATE_LIMIT_MESSAGE
    elif form.is_valid():
        email = form.cleaned_data["email"].lower()
        subscriber, _created = NewsletterSubscriber.objects.get_or_create(email=email, defaults={"source": request.META.get("HTTP_REFERER", "")[:255]})
        if subscriber.unsubscribed_at:
            subscriber.unsubscribed_at = None
            subscriber.confirmed_at = None
            subscriber.consent_at = timezone.now()
            subscriber.save()
        if not subscriber.confirmed_at:
            site = SiteSettings.load()
            confirm_url = request.build_absolute_uri(reverse("crm:newsletter_confirm", args=[subscriber.token]))
            send_email(f"{site.company_name} — confirmez votre inscription", "emails/newsletter_confirm", {"site": site, "url": confirm_url}, [email])
        ok = True
        message = "Merci ! Un email de confirmation vient de vous être envoyé."
    else:
        message = "Merci de saisir une adresse email valide et d'accepter de recevoir la newsletter."
    if is_htmx:
        return render(request, "public/partials/newsletter_result.html", {"ok": ok, "message": message})
    (messages.success if ok else messages.error)(request, message)
    return redirect(request.META.get("HTTP_REFERER") or "/")


def newsletter_confirm(request, token):
    subscriber = get_object_or_404(NewsletterSubscriber, token=token)
    if not subscriber.confirmed_at:
        subscriber.confirmed_at = timezone.now()
        subscriber.unsubscribed_at = None
        subscriber.save()
    return render(request, "public/newsletter_status.html", {"state": "confirmed", "subscriber": subscriber, "seo": build_seo(request, title="Inscription confirmée", noindex=True)})


def newsletter_unsubscribe(request, token):
    subscriber = get_object_or_404(NewsletterSubscriber, token=token)
    if request.method == "POST":
        subscriber.unsubscribed_at = timezone.now()
        subscriber.save()
        state = "unsubscribed"
    else:
        state = "confirm_unsubscribe"
    return render(request, "public/newsletter_status.html", {"state": state, "subscriber": subscriber, "seo": build_seo(request, title="Newsletter", noindex=True)})
