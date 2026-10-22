from django.contrib.auth import views as auth_views
from django.urls import reverse_lazy

from apps.core.models import SiteSettings

from .forms import LoginForm


class BrandContextMixin:
    """Django's auth views inject their own ``site`` variable; restore ours."""

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["site"] = SiteSettings.load()
        return context


class LoginView(BrandContextMixin, auth_views.LoginView):
    template_name = "accounts/login.html"
    authentication_form = LoginForm
    redirect_authenticated_user = True


class LogoutView(auth_views.LogoutView):
    next_page = reverse_lazy("accounts:login")


class PasswordResetView(BrandContextMixin, auth_views.PasswordResetView):
    template_name = "accounts/password_reset.html"
    email_template_name = "accounts/email/password_reset.txt"
    subject_template_name = "accounts/email/password_reset_subject.txt"
    success_url = reverse_lazy("accounts:password_reset_done")


class PasswordResetDoneView(BrandContextMixin, auth_views.PasswordResetDoneView):
    template_name = "accounts/password_reset_done.html"


class PasswordResetConfirmView(BrandContextMixin, auth_views.PasswordResetConfirmView):
    template_name = "accounts/password_reset_confirm.html"
    success_url = reverse_lazy("accounts:password_reset_complete")


class PasswordResetCompleteView(BrandContextMixin, auth_views.PasswordResetCompleteView):
    template_name = "accounts/password_reset_complete.html"


class PasswordChangeView(BrandContextMixin, auth_views.PasswordChangeView):
    template_name = "dashboard/account/password_change.html"
    success_url = reverse_lazy("dashboard:profile")
