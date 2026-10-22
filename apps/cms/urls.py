from django.urls import path

from . import views

app_name = "cms"

urlpatterns = [
    path("", views.home, name="home"),
    path("faq/", views.faq, name="faq"),
    path("carrieres/", views.careers, name="careers"),
    path("carrieres/<slug:slug>/", views.job_detail, name="job_detail"),
    path("partenaires/", views.partners, name="partners"),
]

# Generic CMS pages (/entreprise/, /mentions-legales/…) are routed last from config/urls.py.
page_urlpatterns = [path("<slug:slug>/", views.page_detail, name="page")]
