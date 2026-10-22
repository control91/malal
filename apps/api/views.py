"""Public read-only API v1 — consumed later by the client portal / partners."""

from rest_framework import permissions, viewsets

from apps.blog.models import BlogArticle
from apps.products.models import Product
from apps.services.models import ServiceCategory, StatusComponent

from . import serializers


class ReadOnly(viewsets.ReadOnlyModelViewSet):
    permission_classes = [permissions.AllowAny]
    lookup_field = "slug"


class CategoryViewSet(ReadOnly):
    serializer_class = serializers.ServiceCategorySerializer
    queryset = ServiceCategory.objects.filter(is_published=True)


class ProductViewSet(ReadOnly):
    serializer_class = serializers.ProductSerializer
    queryset = Product.objects.published().select_related("category").prefetch_related("features")


class ArticleViewSet(ReadOnly):
    serializer_class = serializers.ArticleSerializer

    def get_queryset(self):
        return BlogArticle.objects.published().select_related("category")


class StatusViewSet(viewsets.ReadOnlyModelViewSet):
    permission_classes = [permissions.AllowAny]
    serializer_class = serializers.StatusSerializer
    queryset = StatusComponent.objects.filter(is_published=True)
    pagination_class = None
