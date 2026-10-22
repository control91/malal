"""Public forms (contact, quote, demo, newsletter)."""

from django import forms
from django.utils.translation import gettext_lazy as _

from apps.core.antispam import AntiSpamFormMixin
from apps.products.models import Product
from apps.services.models import ServiceCategory

from .models import Inquiry

CONSENT_LABEL = _(
    "J'accepte que mes données soient utilisées pour traiter ma demande, conformément à la politique de confidentialité."
)


class BaseInquiryForm(AntiSpamFormMixin, forms.ModelForm):
    consent = forms.BooleanField(label=CONSENT_LABEL, required=True, error_messages={"required": _("Votre accord est nécessaire pour traiter la demande.")})

    class Meta:
        model = Inquiry
        fields = ["first_name", "last_name", "organization", "email", "phone", "subject", "message"]
        widgets = {
            "message": forms.Textarea(attrs={"rows": 5}),
            "email": forms.EmailInput(attrs={"autocomplete": "email"}),
            "phone": forms.TextInput(attrs={"autocomplete": "tel", "inputmode": "tel"}),
            "first_name": forms.TextInput(attrs={"autocomplete": "given-name"}),
            "last_name": forms.TextInput(attrs={"autocomplete": "family-name"}),
            "organization": forms.TextInput(attrs={"autocomplete": "organization"}),
        }

    kind_value = Inquiry.Kind.CONTACT

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        for name in ("category", "product"):
            if name in self.fields:
                self.fields[name].required = False
        if "category" in self.fields:
            self.fields["category"].queryset = ServiceCategory.objects.filter(is_published=True)
            self.fields["category"].empty_label = _("— Choisir un domaine —")
        if "product" in self.fields:
            self.fields["product"].queryset = Product.objects.published()
            self.fields["product"].empty_label = _("— Choisir une solution —")

    def clean_message(self):
        message = self.cleaned_data.get("message", "")
        if message.count("http") > 4:
            raise forms.ValidationError(_("Votre message contient trop de liens."))
        return message


class ContactForm(BaseInquiryForm):
    KIND_CHOICES = [
        (Inquiry.Kind.CONTACT, _("Question générale")),
        (Inquiry.Kind.QUOTE, _("Projet / devis")),
        (Inquiry.Kind.PARTNERSHIP, _("Partenariat")),
        (Inquiry.Kind.SUPPORT, _("Support")),
        (Inquiry.Kind.CAREER, _("Candidature spontanée")),
    ]
    kind = forms.ChoiceField(label=_("Type de demande"), choices=KIND_CHOICES)

    class Meta(BaseInquiryForm.Meta):
        fields = ["kind", "first_name", "last_name", "organization", "email", "phone", "category", "subject", "message"]


class QuoteForm(BaseInquiryForm):
    kind_value = Inquiry.Kind.QUOTE

    class Meta(BaseInquiryForm.Meta):
        fields = [
            "first_name", "last_name", "organization", "job_title", "email", "phone", "country",
            "category", "product", "plan", "budget", "timeline", "subject", "message",
        ]
        widgets = {**BaseInquiryForm.Meta.widgets, "plan": forms.HiddenInput}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["plan"].required = False
        self.fields["organization"].required = True
        self.fields["message"].label = _("Décrivez votre projet")


class DemoForm(BaseInquiryForm):
    kind_value = Inquiry.Kind.DEMO

    class Meta(BaseInquiryForm.Meta):
        fields = ["product", "first_name", "last_name", "organization", "job_title", "email", "phone", "country", "message"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["product"].queryset = Product.objects.published().filter(demo_enabled=True)
        self.fields["product"].required = True
        self.fields["organization"].required = True
        self.fields["message"].required = False
        self.fields["message"].label = _("Vos besoins (facultatif)")

    def save(self, commit=True):
        obj = super().save(commit=False)
        obj.subject = f"Démonstration — {obj.product}" if obj.product else "Démonstration"
        if not obj.message:
            obj.message = "Demande de démonstration envoyée depuis le site."
        if commit:
            obj.save()
        return obj


class NewsletterForm(AntiSpamFormMixin, forms.Form):
    email = forms.EmailField(label=_("Adresse email"), widget=forms.EmailInput(attrs={"placeholder": _("votre@email.com"), "autocomplete": "email"}))
    consent = forms.BooleanField(label=_("J'accepte de recevoir la newsletter (désinscription en un clic)."), required=True)
