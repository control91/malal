from django.urls import path

from . import views

app_name = "products"

urlpatterns = [
    path("solutions/", views.catalog, name="catalog"),
    path("solutions/<slug:slug>/", views.product_detail, name="detail"),
]
