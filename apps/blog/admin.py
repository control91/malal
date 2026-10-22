from django.contrib import admin

from . import models


@admin.register(models.BlogArticle)
class BlogArticleAdmin(admin.ModelAdmin):
    list_display = ["title", "status", "published_at", "author"]
    list_filter = ["status", "category"]
    prepopulated_fields = {"slug": ["title"]}
    search_fields = ["title"]


admin.site.register(models.BlogCategory)
admin.site.register(models.BlogTag)
