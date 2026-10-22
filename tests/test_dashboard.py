import pytest
from django.urls import reverse

from apps.accounts.models import User
from apps.accounts.roles import Role
from apps.audit.models import AuditLog
from apps.blog.models import BlogArticle
from apps.cms.models import Page, PageSection
from apps.core.models import SiteSettings
from apps.crm.models import Inquiry, Lead

pytestmark = pytest.mark.django_db


def test_dashboard_requires_login(client):
    response = client.get("/dashboard/")
    assert response.status_code == 302 and "/comptes/connexion/" in response["Location"]


def test_non_staff_user_is_refused(client, db):
    user = User.objects.create_user(email="client@test.local", password="x")
    client.force_login(user)
    assert client.get("/dashboard/").status_code == 403


@pytest.mark.parametrize("role,url,expected", [
    (Role.EDITOR, "/dashboard/articles/", 200),
    (Role.EDITOR, "/dashboard/demandes/", 403),
    (Role.EDITOR, "/dashboard/parametres/", 403),
    (Role.EDITOR, "/dashboard/utilisateurs/", 403),
    (Role.SALES, "/dashboard/prospects/", 200),
    (Role.SALES, "/dashboard/pipeline/", 200),
    (Role.SALES, "/dashboard/pages/", 403),
    (Role.SALES, "/dashboard/audit/", 403),
    (Role.SUPPORT, "/dashboard/demandes/", 200),
    (Role.SUPPORT, "/dashboard/prospects/nouveau/", 403),
    (Role.VIEWER, "/dashboard/articles/", 200),
    (Role.VIEWER, "/dashboard/articles/nouveau/", 403),
    (Role.VIEWER, "/dashboard/utilisateurs/", 403),
    (Role.ADMIN, "/dashboard/utilisateurs/", 200),
    (Role.ADMIN, "/dashboard/audit/", 200),
    (Role.ADMIN, "/dashboard/parametres/", 200),
])
def test_role_access_matrix(staff_client, site_content, role, url, expected):
    client, _user = staff_client(role)
    assert client.get(url).status_code == expected


def test_viewer_cannot_post_even_on_readable_object(staff_client, site_content):
    client, _ = staff_client(Role.VIEWER)
    page = Page.objects.get(slug="entreprise")
    response = client.post(reverse("dashboard:pages_update", args=[page.pk]), {"title": "Hacked", "slug": "entreprise", "template": "standard", "status": "published"})
    assert response.status_code == 403
    page.refresh_from_db()
    assert page.title != "Hacked"


def test_editor_creates_article_and_it_is_audited(staff_client, site_content):
    client, user = staff_client(Role.EDITOR)
    response = client.post(reverse("dashboard:articles_create"), {
        "title": "Nouvel article", "slug": "nouvel-article", "excerpt": "", "body": "<p>Corps</p><script>x</script>",
        "status": "published", "published_at": "", "tags": [],
    })
    assert response.status_code == 302, response.content[:2000]
    article = BlogArticle.objects.get(slug="nouvel-article")
    assert article.author == user and article.is_live and "<script" not in article.body
    actions = set(AuditLog.objects.filter(object_type="blog.BlogArticle").values_list("action", flat=True))
    assert {"create", "publish"} <= actions


def test_toggle_publish_and_delete_are_audited(staff_client, site_content):
    client, _ = staff_client(Role.EDITOR)
    article = BlogArticle.objects.create(title="A", slug="a", body="<p>a</p>")
    client.post(reverse("dashboard:articles_toggle", args=[article.pk]))
    article.refresh_from_db()
    assert article.status == "published"
    client.post(reverse("dashboard:articles_delete", args=[article.pk]))
    assert not BlogArticle.objects.filter(pk=article.pk).exists()
    assert AuditLog.objects.filter(action="delete", object_repr="A").exists()


def test_system_pages_cannot_be_deleted(staff_client, site_content):
    client, _ = staff_client(Role.ADMIN)
    page = Page.objects.get(slug="accueil")
    client.post(reverse("dashboard:pages_delete", args=[page.pk]))
    assert Page.objects.filter(pk=page.pk).exists()


def test_section_edit_with_inline_items(staff_client, site_content):
    client, _ = staff_client(Role.EDITOR)
    section = PageSection.objects.get(page__slug="accueil", kind="hero")
    items = list(section.items.all())
    data = {
        "page": section.page_id, "kind": "hero", "theme": "dark", "order": 0, "is_active": "on", "anchor": "",
        "eyebrow": "E", "title": "Nouveau titre", "subtitle": "S", "body": "", "image_alt": "",
        "cta_label": "Go", "cta_url": "/solutions/", "cta2_label": "", "cta2_url": "", "limit": 6,
        "inline0-TOTAL_FORMS": len(items) + 1, "inline0-INITIAL_FORMS": len(items), "inline0-MIN_NUM_FORMS": 0, "inline0-MAX_NUM_FORMS": 1000,
    }
    for i, item in enumerate(items):
        data.update({f"inline0-{i}-id": item.pk, f"inline0-{i}-section": section.pk, f"inline0-{i}-order": item.order, f"inline0-{i}-title": item.title,
                     f"inline0-{i}-icon": item.icon, f"inline0-{i}-text": "", f"inline0-{i}-badge": "", f"inline0-{i}-progress": "", f"inline0-{i}-link_label": "", f"inline0-{i}-link_url": ""})
    data["inline0-0-DELETE"] = "on"
    n = len(items)
    data.update({f"inline0-{n}-order": 99, f"inline0-{n}-title": "Nouvel élément", f"inline0-{n}-icon": "cloud", f"inline0-{n}-section": section.pk})
    response = client.post(reverse("dashboard:sections_update", args=[section.pk]), data)
    assert response.status_code == 302, response.content.decode()[:3000]
    section.refresh_from_db()
    titles = list(section.items.values_list("title", flat=True))
    assert section.title == "Nouveau titre" and "Nouvel élément" in titles and items[0].title not in titles


def test_settings_update_is_audited_and_validated(staff_client, site_content):
    client, _ = staff_client(Role.ADMIN)
    site = SiteSettings.load()
    from apps.dashboard.forms import SiteSettingsForm

    data = {k: v for k, v in SiteSettingsForm(instance=site).initial.items() if v not in (None, "") and not hasattr(v, "url")}
    data.update({"company_name": "Nouvelle Marque", "enabled_languages": "fr,en", "color_primary": "#123456"})
    for key in ("notify_on_inquiry", "send_acknowledgement"):
        data[key] = "on"
    response = client.post(reverse("dashboard:settings"), data)
    assert response.status_code == 302, response.content.decode()[:2000]
    assert SiteSettings.load().company_name == "Nouvelle Marque"
    assert AuditLog.objects.filter(action="settings").exists()
    data["color_primary"] = "red"
    assert client.post(reverse("dashboard:settings"), data).status_code == 200  # re-rendered with errors


def test_admin_cannot_create_superadmin_or_edit_one(staff_client, db):
    client, admin = staff_client(Role.ADMIN)
    response = client.post(reverse("dashboard:utilisateurs_create"), {"email": "x@test.local", "role": "superadmin", "is_active": "on", "password": "Another-str0ng-pass!"})
    assert response.status_code == 200 and not User.objects.filter(email="x@test.local").exists()
    boss = User.objects.create_superuser(email="boss@test.local", password="Boss-str0ng-pass!!")
    response = client.post(reverse("dashboard:utilisateurs_update", args=[boss.pk]), {"email": "boss@test.local", "role": "viewer", "is_active": "on"})
    boss.refresh_from_db()
    assert boss.is_superuser


def test_role_change_is_audited(staff_client, make_user):
    client, _ = staff_client(Role.ADMIN)
    target = make_user(Role.VIEWER, email="t@test.local")
    response = client.post(reverse("dashboard:utilisateurs_update", args=[target.pk]), {"email": "t@test.local", "role": "editor", "is_active": "on"})
    assert response.status_code == 302
    target.refresh_from_db()
    assert target.role == "editor"
    assert AuditLog.objects.filter(action="role_change", object_id=str(target.pk)).exists()


def _inquiry():
    lead = Lead.objects.create(first_name="A", email="a@example.com")
    return Inquiry.objects.create(first_name="A", last_name="B", email="a@example.com", subject="S", message="M", lead=lead)


def test_inquiry_workflow(staff_client, db):
    from django.core import mail

    client, user = staff_client(Role.SALES)
    inquiry = _inquiry()
    client.post(reverse("dashboard:inquiry_update", args=[inquiry.pk]), {"status": "in_progress", "assigned_to": user.pk})
    client.post(reverse("dashboard:inquiry_note", args=[inquiry.pk]), {"kind": "note", "content": "Rappeler lundi"})
    client.post(reverse("dashboard:inquiry_reply", args=[inquiry.pk]), {"subject": "Re", "message": "Bonjour", "mark_answered": "on"})
    inquiry.refresh_from_db()
    assert inquiry.status == "answered" and inquiry.assigned_to == user and inquiry.answered_at
    assert len(mail.outbox) == 1 and mail.outbox[0].to == ["a@example.com"]
    kinds = set(inquiry.activities.values_list("kind", flat=True))
    assert {"status", "assignment", "note", "email"} <= kinds
    assert {"status", "assign", "email"} <= set(AuditLog.objects.values_list("action", flat=True))


def test_support_cannot_change_lead_status(staff_client, db):
    client, _ = staff_client(Role.SUPPORT)
    lead = Lead.objects.create(first_name="A", email="a@example.com")
    assert client.post(reverse("dashboard:lead_status", args=[lead.pk]), {"status": "won"}).status_code == 403


def test_lead_conversion(staff_client, db):
    client, _ = staff_client(Role.SALES)
    lead = Lead.objects.create(first_name="A", email="a@example.com")
    client.post(reverse("dashboard:lead_convert", args=[lead.pk]))
    lead.refresh_from_db()
    assert lead.is_client and lead.status == "won" and lead.converted_at
    assert AuditLog.objects.filter(action="convert").exists()


def test_csv_export_guards_formula_injection(staff_client, db):
    client, _ = staff_client(Role.SALES)
    lead = Lead.objects.create(first_name="=HYPERLINK(1)", email="a@example.com")
    Inquiry.objects.create(first_name="=cmd", last_name="B", email="a@example.com", subject="S", message="M", lead=lead)
    content = client.get(reverse("dashboard:demandes_export")).content.decode("utf-8-sig")
    assert "'=cmd" in content
    assert AuditLog.objects.filter(action="export").exists()


def test_login_is_audited_and_rate_limited(client, make_user):
    make_user(Role.ADMIN, email="lock@test.local")
    for _ in range(5):
        client.post("/comptes/connexion/", {"username": "lock@test.local", "password": "wrong"})
    assert AuditLog.objects.filter(action="login_failed").count() >= 5
    response = client.post("/comptes/connexion/", {"username": "lock@test.local", "password": "Str0ng-pass-for-tests!"})
    assert response.status_code in (403, 429) or "bloqué" in response.content.decode()
