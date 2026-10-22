from django.contrib import admin

from . import models


class FeatureInline(admin.TabularInline):
    model = models.ProductFeature
    extra = 0


class ScreenshotInline(admin.TabularInline):
    model = models.ProductScreenshot
    extra = 0


@admin.register(models.Product)
class ProductAdmin(admin.ModelAdmin):
    list_display = ["name", "category", "status", "is_published", "is_featured"]
    list_filter = ["category", "status", "is_published"]
    prepopulated_fields = {"slug": ["name"]}
    inlines = [FeatureInline, ScreenshotInline]
