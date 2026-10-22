from django.urls import path

from . import views

app_name = "crm"

urlpatterns = [
    path("contact/", views.contact, name="contact"),
    path("devis/", views.quote, name="quote"),
    path("demo/", views.demo, name="demo"),
    path("merci/", views.thanks, name="thanks"),
    path("newsletter/inscription/", views.newsletter_subscribe, name="newsletter_subscribe"),
    path("newsletter/confirmer/<str:token>/", views.newsletter_confirm, name="newsletter_confirm"),
    path("newsletter/desinscription/<str:token>/", views.newsletter_unsubscribe, name="newsletter_unsubscribe"),
]
