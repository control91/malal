"""
Anti-spam for public forms without third-party CAPTCHA (privacy friendly, works on
slow connections):

* a honeypot field that humans never see;
* a signed timestamp: forms posted faster than ``FORM_MIN_SUBMIT_SECONDS`` are rejected;
* a per-IP rate limit stored in the cache.
"""

import hashlib
import time

from django import forms
from django.conf import settings
from django.core import signing
from django.core.cache import cache
from django.utils.translation import gettext_lazy as _

from .middleware import client_ip

SIGNER_SALT = "antispam.form-ts"


class AntiSpamFormMixin(forms.Form):
    website = forms.CharField(required=False, label=_("Laissez ce champ vide"), widget=forms.TextInput(attrs={"autocomplete": "off", "tabindex": "-1"}))
    form_ts = forms.CharField(required=False, widget=forms.HiddenInput)

    spam_detected = False

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if not self.is_bound:
            self.initial["form_ts"] = signing.dumps(int(time.time()), salt=SIGNER_SALT)

    def clean(self):
        cleaned = super().clean()
        if cleaned.get("website"):
            self.spam_detected = True
            raise forms.ValidationError(_("Votre demande n'a pas pu être envoyée."), code="spam")
        try:
            issued = signing.loads(cleaned.get("form_ts") or "", salt=SIGNER_SALT, max_age=60 * 60 * 24)
        except signing.BadSignature:
            raise forms.ValidationError(_("Le formulaire a expiré. Merci de recharger la page."), code="expired")
        if time.time() - issued < settings.FORM_MIN_SUBMIT_SECONDS:
            self.spam_detected = True
            raise forms.ValidationError(_("Votre demande n'a pas pu être envoyée. Merci de réessayer."), code="spam")
        return cleaned


def rate_limited(request, scope: str) -> bool:
    """Return True when the client IP exceeded the configured rate for ``scope``."""
    limit, _sep, window = settings.FORM_RATE_LIMIT.partition("/")
    limit, window = int(limit), int(window or 600)
    ip_hash = hashlib.sha256(client_ip(request).encode()).hexdigest()[:24]
    key = f"ratelimit:{scope}:{ip_hash}"
    added = cache.add(key, 1, window)
    if added:
        return False
    try:
        count = cache.incr(key)
    except ValueError:
        cache.set(key, 1, window)
        return False
    return count > limit
