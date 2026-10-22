import time

import pytest
from django.core import mail, signing
from django.test import override_settings

from apps.core.antispam import SIGNER_SALT
from apps.crm.models import Activity, Inquiry, Lead, NewsletterSubscriber
from apps.notifications.models import Notification

pytestmark = pytest.mark.django_db


def contact_payload(**extra):
    data = {
        "kind": "contact", "first_name": "Awa", "last_name": "Test", "organization": "Org", "email": "awa@example.com",
        "phone": "", "subject": "Question", "message": "Bonjour, une question.", "consent": "on",
        "website": "", "form_ts": signing.dumps(int(time.time()) - 10, salt=SIGNER_SALT),
    }
    data.update(extra)
    return data


def test_contact_creates_inquiry_lead_activity_notification_and_emails(client, site_content, make_user, django_capture_on_commit_callbacks):
    sales = make_user("sales")
    make_user("editor")
    with django_capture_on_commit_callbacks(execute=True):
        response = client.post("/contact/", contact_payload())
    assert response.status_code == 302 and response["Location"] == "/merci/"
    inquiry = Inquiry.objects.get()
    assert inquiry.consent and inquiry.consent_at and inquiry.reference.startswith("CON-")
    assert inquiry.lead and inquiry.lead.email == "awa@example.com"
    assert Activity.objects.filter(inquiry=inquiry, kind="inquiry").exists()
    # Only roles holding inquiries.view are notified (sales yes, editor no).
    assert list(Notification.objects.values_list("recipient", flat=True)) == [sales.pk]
    subjects = [m.subject for m in mail.outbox]
    assert any(inquiry.reference in s for s in subjects)
    assert any("bien reçu" in s for s in subjects)
    assert inquiry.reference in client.get("/merci/").content.decode()


def test_second_inquiry_reuses_existing_lead(client, site_content):
    client.post("/contact/", contact_payload())
    client.post("/contact/", contact_payload(email="AWA@example.com", subject="Autre"))
    assert Lead.objects.count() == 1 and Inquiry.objects.count() == 2


def test_consent_is_required(client, site_content):
    payload = contact_payload()
    payload.pop("consent")
    response = client.post("/contact/", payload)
    assert response.status_code == 400 and not Inquiry.objects.exists()


def test_honeypot_blocks_bots(client, site_content):
    response = client.post("/contact/", contact_payload(website="http://spam"))
    assert response.status_code == 400 and not Inquiry.objects.exists()


@override_settings(FORM_MIN_SUBMIT_SECONDS=5)
def test_too_fast_submission_is_rejected(client, site_content):
    response = client.post("/contact/", contact_payload(form_ts=signing.dumps(int(time.time()), salt=SIGNER_SALT)))
    assert response.status_code == 400 and not Inquiry.objects.exists()


def test_tampered_timestamp_is_rejected(client, site_content):
    response = client.post("/contact/", contact_payload(form_ts="forged"))
    assert response.status_code == 400 and not Inquiry.objects.exists()


@override_settings(FORM_RATE_LIMIT="2/600")
def test_rate_limit(client, site_content):
    for i in range(2):
        assert client.post("/contact/", contact_payload(subject=f"s{i}")).status_code == 302
    response = client.post("/contact/", contact_payload(subject="s3"))
    assert response.status_code == 400 and Inquiry.objects.count() == 2


def test_demo_request_requires_product(client, site_content):
    response = client.post("/demo/", contact_payload(kind=""))
    assert response.status_code == 400


def test_thanks_page_without_submission_redirects(client, site_content):
    assert client.get("/merci/").status_code == 302


def test_newsletter_double_opt_in(client, site_content):
    response = client.post("/newsletter/inscription/", {"email": "nl@example.com", "consent": "on", "form_ts": contact_payload()["form_ts"]}, HTTP_HX_REQUEST="true")
    assert response.status_code == 200
    sub = NewsletterSubscriber.objects.get()
    assert not sub.is_active and len(mail.outbox) == 1
    client.get(f"/newsletter/confirmer/{sub.token}/")
    sub.refresh_from_db()
    assert sub.is_active
    client.post(f"/newsletter/desinscription/{sub.token}/")
    sub.refresh_from_db()
    assert not sub.is_active
