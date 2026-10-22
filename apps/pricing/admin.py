from django.contrib import admin

from . import models


class PlanFeatureInline(admin.TabularInline):
    model = models.PricingFeature
    extra = 0


@admin.register(models.PricingPlan)
class PricingPlanAdmin(admin.ModelAdmin):
    list_display = ["name", "group", "price_monthly", "price_yearly", "currency", "is_published"]
    inlines = [PlanFeatureInline]


admin.site.register(models.PricingGroup)
