from django.urls import path

from . import views

app_name = "services"

urlpatterns = [
    path("expertises/<slug:slug>/", views.category_detail, name="category"),
    path("statut/", views.status_page, name="status"),
]
