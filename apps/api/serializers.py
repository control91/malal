from drf_spectacular.utils import extend_schema_field
from rest_framework import serializers

from apps.blog.models import BlogArticle
from apps.products.models import Product, ProductFeature
from apps.services.models import Service, ServiceCategory, StatusComponent


class ServiceSerializer(serializers.ModelSerializer):
    class Meta:
        model = Service
        fields = ["name", "slug", "summary", "status"]


class ServiceCategorySerializer(serializers.ModelSerializer):
    services = serializers.SerializerMethodField()
    url = serializers.CharField(source="get_absolute_url", read_only=True)

    class Meta:
        model = ServiceCategory
        fields = ["name", "slug", "tagline", "summary", "url", "services"]

    @extend_schema_field(ServiceSerializer(many=True))
    def get_services(self, obj):
        return ServiceSerializer(obj.services.filter(is_published=True), many=True).data


class FeatureSerializer(serializers.ModelSerializer):
    class Meta:
        model = ProductFeature
        fields = ["title", "description"]


class ProductSerializer(serializers.ModelSerializer):
    category = serializers.SlugRelatedField(slug_field="slug", read_only=True)
    features = FeatureSerializer(many=True, read_only=True)
    url = serializers.CharField(source="get_absolute_url", read_only=True)

    class Meta:
        model = Product
        fields = ["name", "slug", "tagline", "summary", "status", "category", "url", "features"]


class ArticleSerializer(serializers.ModelSerializer):
    category = serializers.SlugRelatedField(slug_field="slug", read_only=True)
    url = serializers.CharField(source="get_absolute_url", read_only=True)

    class Meta:
        model = BlogArticle
        fields = ["title", "slug", "excerpt", "category", "published_at", "url"]


class StatusSerializer(serializers.ModelSerializer):
    class Meta:
        model = StatusComponent
        fields = ["name", "state", "message", "updated_at"]
