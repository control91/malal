from django.urls import include, path
from drf_spectacular.views import SpectacularAPIView
from rest_framework.routers import DefaultRouter

from . import views

app_name = "api"

router = DefaultRouter()
router.register("expertises", views.CategoryViewSet, basename="category")
router.register("solutions", views.ProductViewSet, basename="product")
router.register("articles", views.ArticleViewSet, basename="article")
router.register("statut", views.StatusViewSet, basename="status")

urlpatterns = [
    path("v1/", include(router.urls)),
    path("schema/", SpectacularAPIView.as_view(), name="schema"),
]
