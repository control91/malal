from django import forms
from django.contrib.auth.forms import AuthenticationForm
from django.utils.translation import gettext_lazy as _


class LoginForm(AuthenticationForm):
    username = forms.EmailField(label=_("Adresse email"), widget=forms.EmailInput(attrs={"autofocus": True, "autocomplete": "email"}))
    password = forms.CharField(label=_("Mot de passe"), strip=False, widget=forms.PasswordInput(attrs={"autocomplete": "current-password"}))

    error_messages = {
        "invalid_login": _("Identifiants incorrects. Après plusieurs échecs, l'accès est temporairement bloqué."),
        "inactive": _("Ce compte est désactivé."),
    }

    def confirm_login_allowed(self, user):
        super().confirm_login_allowed(user)
        if not user.is_staff:
            raise forms.ValidationError(_("Ce compte n'a pas accès à l'administration."), code="no_access")
