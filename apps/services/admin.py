from django.contrib import admin

from . import models


class ServiceInline(admin.TabularInline):
    model = models.Service
    extra = 0


@admin.register(models.ServiceCategory)
class ServiceCategoryAdmin(admin.ModelAdmin):
    list_display = ["name", "slug", "order", "is_published"]
    prepopulated_fields = {"slug": ["name"]}
    inlines = [ServiceInline]


admin.site.register(models.StatusComponent)
