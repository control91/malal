from django.urls import path

from . import views

app_name = "portfolio"

urlpatterns = [
    path("realisations/", views.case_list, name="list"),
    path("realisations/<slug:slug>/", views.case_detail, name="detail"),
]
