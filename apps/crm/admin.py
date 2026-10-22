from django.contrib import admin

from . import models


@admin.register(models.Inquiry)
class InquiryAdmin(admin.ModelAdmin):
    list_display = ["reference", "kind", "status", "email", "created_at"]
    list_filter = ["kind", "status"]
    search_fields = ["reference", "email", "last_name"]


@admin.register(models.Lead)
class LeadAdmin(admin.ModelAdmin):
    list_display = ["__str__", "status", "owner", "created_at"]
    list_filter = ["status"]
    search_fields = ["email", "last_name", "organization"]


admin.site.register(models.Activity)
admin.site.register(models.NewsletterSubscriber)
