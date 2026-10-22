from django.urls import path

from apps.accounts.views import PasswordChangeView

from . import (
    resources,  # noqa: F401  (registers the resources)
    views,
)
from .crud import registry

app_name = "dashboard"

urlpatterns = [
    path("", views.home, name="home"),
    path("recherche/", views.search, name="search"),
    path("demandes/<int:pk>/detail/", views.inquiry_detail, name="inquiry_detail"),
    path("demandes/<int:pk>/maj/", views.inquiry_update, name="inquiry_update"),
    path("demandes/<int:pk>/commentaire/", views.inquiry_note, name="inquiry_note"),
    path("demandes/<int:pk>/repondre/", views.inquiry_reply, name="inquiry_reply"),
    path("prospects/<int:pk>/fiche/", views.lead_detail, name="lead_detail"),
    path("prospects/<int:pk>/statut/", views.lead_status, name="lead_status"),
    path("prospects/<int:pk>/interaction/", views.lead_note, name="lead_note"),
    path("prospects/<int:pk>/relance/", views.lead_followup, name="lead_followup"),
    path("prospects/<int:pk>/convertir/", views.lead_convert, name="lead_convert"),
    path("pipeline/", views.pipeline, name="pipeline"),
    path("relances/", views.followups, name="followups"),
    path("sections/<int:pk>/deplacer/<str:direction>/", views.section_move, name="section_move"),
    path("parametres/", views.site_settings, name="settings"),
    path("profil/", views.profile, name="profile"),
    path("profil/mot-de-passe/", PasswordChangeView.as_view(), name="password_change"),
    path("notifications/", views.notifications, name="notifications"),
    path("notifications/menu/", views.notifications_menu, name="notifications_menu"),
    path("notifications/lu/", views.notification_read, name="notifications_read_all"),
    path("notifications/<int:pk>/lu/", views.notification_read, name="notification_read"),
    path("statistiques/", views.analytics, name="analytics"),
    path("ping/", views.keepalive, name="keepalive"),
]

for resource in registry.values():
    urlpatterns += resource.urlpatterns()
