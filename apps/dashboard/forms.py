"""Base form styling for the back-office and the forms of special screens."""

from django import forms
from django.contrib.auth import get_user_model, password_validation
from django.utils.translation import gettext_lazy as _

from apps.accounts.roles import Role
from apps.core.models import SiteSettings
from apps.core.widgets import ColorWidget, IconSelectWidget
from apps.crm.models import Activity, Inquiry, Lead

DATETIME_FORMAT = "%Y-%m-%dT%H:%M"


def style_fields(form: forms.BaseForm) -> None:
    """Attach design-system classes and the right HTML5 widgets to every field."""
    for name, field in form.fields.items():
        widget = field.widget
        if name == "icon" and isinstance(widget, forms.Select) and not isinstance(widget, IconSelectWidget):
            field.widget = widget = IconSelectWidget(choices=widget.choices)
        if isinstance(field, forms.SplitDateTimeField):
            continue
        if isinstance(field, forms.DateTimeField):
            field.widget = widget = forms.DateTimeInput(attrs={"type": "datetime-local"}, format=DATETIME_FORMAT)
            field.input_formats = [DATETIME_FORMAT, "%Y-%m-%d %H:%M", "%Y-%m-%d %H:%M:%S"]
        elif isinstance(field, forms.DateField):
            field.widget = widget = forms.DateInput(attrs={"type": "date"}, format="%Y-%m-%d")
        if name.startswith("color_"):
            field.widget = widget = ColorWidget()
        if isinstance(widget, forms.CheckboxInput):
            css = "field-checkbox"
        elif isinstance(widget, (forms.Select, forms.SelectMultiple)) and not isinstance(widget, forms.CheckboxSelectMultiple):
            css = "field-select"
        elif isinstance(widget, forms.ClearableFileInput) or isinstance(widget, forms.FileInput):
            css = "field-file"
        elif isinstance(widget, forms.CheckboxSelectMultiple):
            css = "field-checks"
        else:
            css = "field-input"
        widget.attrs["class"] = f"{widget.attrs.get('class', '')} {css}".strip()
        if isinstance(widget, forms.Textarea) and "rows" not in widget.attrs:
            widget.attrs["rows"] = 4
        if isinstance(widget, forms.SelectMultiple) and not isinstance(widget, forms.CheckboxSelectMultiple):
            widget.attrs.setdefault("size", 6)


class DashboardModelForm(forms.ModelForm):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        style_fields(self)


class StyledForm(forms.Form):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        style_fields(self)


# ---------------------------------------------------------------------------
# Users
# ---------------------------------------------------------------------------
class UserForm(DashboardModelForm):
    password = forms.CharField(
        label=_("Mot de passe"), required=False, strip=False, widget=forms.PasswordInput(render_value=False),
        help_text=_("Laisser vide pour ne pas le modifier. 12 caractères minimum."),
    )

    class Meta:
        model = get_user_model()
        fields = ["email", "first_name", "last_name", "job_title", "phone", "role", "is_active", "avatar", "bio"]

    def __init__(self, *args, request_user=None, **kwargs):
        super().__init__(*args, **kwargs)
        self.request_user = request_user
        if request_user is not None and not request_user.is_superuser:
            self.fields["role"].choices = [c for c in Role.choices if c[0] != Role.SUPERADMIN]
        if not self.instance.pk:
            self.fields["password"].required = True
            self.fields["password"].help_text = _("12 caractères minimum, évitez les mots de passe courants.")
        if self.instance.pk and request_user is not None and self.instance.pk == request_user.pk:
            # Nobody can lock themselves out or self-promote.
            self.fields["role"].disabled = True
            self.fields["is_active"].disabled = True

    def clean_password(self):
        password = self.cleaned_data.get("password")
        if password:
            password_validation.validate_password(password, self.instance)
        return password

    def clean(self):
        cleaned = super().clean()
        target_is_super = self.instance.pk and self.instance.is_superuser
        if self.request_user is not None and not self.request_user.is_superuser and target_is_super:
            raise forms.ValidationError(_("Seul un super administrateur peut modifier ce compte."))
        return cleaned

    def save(self, commit=True):
        user = super().save(commit=False)
        user.is_staff = True
        if user.role != Role.SUPERADMIN:
            user.is_superuser = False
        if password := self.cleaned_data.get("password"):
            user.set_password(password)
        if commit:
            user.save()
        return user


class ProfileForm(DashboardModelForm):
    class Meta:
        model = get_user_model()
        fields = ["first_name", "last_name", "job_title", "phone", "avatar", "bio"]


# ---------------------------------------------------------------------------
# Site settings
# ---------------------------------------------------------------------------
class SiteSettingsForm(DashboardModelForm):
    class Meta:
        model = SiteSettings
        exclude = ["created_at", "updated_at"]
        widgets = {"hosting_disclosure": forms.Textarea(attrs={"rows": 3}), "footer_text": forms.Textarea(attrs={"rows": 3}), "notification_emails": forms.Textarea(attrs={"rows": 3})}

    def clean_enabled_languages(self):
        from django.conf import settings

        allowed = {code for code, _label in settings.LANGUAGES}
        codes = [c.strip() for c in self.cleaned_data["enabled_languages"].split(",") if c.strip()]
        unknown = [c for c in codes if c not in allowed]
        if unknown:
            raise forms.ValidationError(_("Langue(s) non prise(s) en charge : %(codes)s"), params={"codes": ", ".join(unknown)})
        if "fr" not in codes:
            raise forms.ValidationError(_("Le français doit rester actif."))
        return ",".join(codes)

    def clean_notification_emails(self):
        from django.core.validators import validate_email

        lines = [line.strip() for line in self.cleaned_data["notification_emails"].splitlines() if line.strip()]
        for line in lines:
            try:
                validate_email(line)
            except forms.ValidationError:
                raise forms.ValidationError(_("Adresse invalide : %(email)s"), params={"email": line})
        return "\n".join(lines)


# ---------------------------------------------------------------------------
# CRM screens
# ---------------------------------------------------------------------------
class InquiryUpdateForm(DashboardModelForm):
    class Meta:
        model = Inquiry
        fields = ["status", "assigned_to"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["assigned_to"].queryset = get_user_model().objects.filter(is_active=True, is_staff=True)


class ActivityForm(DashboardModelForm):
    class Meta:
        model = Activity
        fields = ["kind", "content"]
        widgets = {"content": forms.Textarea(attrs={"rows": 3, "placeholder": _("Compte rendu, prochaine étape…")})}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        manual = [Activity.Kind.NOTE, Activity.Kind.CALL, Activity.Kind.EMAIL, Activity.Kind.MEETING, Activity.Kind.DEMO, Activity.Kind.FOLLOW_UP]
        self.fields["kind"].choices = [(k.value, k.label) for k in manual]


class ReplyForm(StyledForm):
    subject = forms.CharField(label=_("Objet"), max_length=200)
    message = forms.CharField(label=_("Message"), widget=forms.Textarea(attrs={"rows": 8}))
    mark_answered = forms.BooleanField(label=_("Marquer la demande comme répondue"), required=False, initial=True)


class LeadForm(DashboardModelForm):
    class Meta:
        model = Lead
        fields = [
            "first_name", "last_name", "email", "phone", "organization", "job_title", "country",
            "source", "status", "priority", "owner", "interest", "product", "estimated_value", "currency",
            "next_follow_up_at", "follow_up_note", "lost_reason", "notes",
        ]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["owner"].queryset = get_user_model().objects.filter(is_active=True, is_staff=True)


class LeadStatusForm(StyledForm):
    status = forms.ChoiceField(choices=Lead.Status.choices)


class FollowUpForm(StyledForm):
    next_follow_up_at = forms.DateTimeField(label=_("Date de relance"), required=False)
    follow_up_note = forms.CharField(label=_("Objet"), max_length=200, required=False)
