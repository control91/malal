from django.urls import path

from . import views

app_name = "accounts"

urlpatterns = [
    path("connexion/", views.LoginView.as_view(), name="login"),
    path("deconnexion/", views.LogoutView.as_view(), name="logout"),
    path("mot-de-passe/oublie/", views.PasswordResetView.as_view(), name="password_reset"),
    path("mot-de-passe/envoye/", views.PasswordResetDoneView.as_view(), name="password_reset_done"),
    path("mot-de-passe/<uidb64>/<token>/", views.PasswordResetConfirmView.as_view(), name="password_reset_confirm"),
    path("mot-de-passe/termine/", views.PasswordResetCompleteView.as_view(), name="password_reset_complete"),
]
