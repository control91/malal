from django.contrib import admin

from . import models


class SectionItemInline(admin.TabularInline):
    model = models.SectionItem
    extra = 0


class PageSectionInline(admin.StackedInline):
    model = models.PageSection
    extra = 0
    show_change_link = True


@admin.register(models.Page)
class PageAdmin(admin.ModelAdmin):
    list_display = ["title", "slug", "template", "status"]
    prepopulated_fields = {"slug": ["title"]}
    inlines = [PageSectionInline]


@admin.register(models.PageSection)
class PageSectionAdmin(admin.ModelAdmin):
    list_display = ["page", "kind", "title", "order", "is_active"]
    list_filter = ["page", "kind"]
    inlines = [SectionItemInline]


class NavigationItemInline(admin.TabularInline):
    model = models.NavigationItem
    extra = 0


@admin.register(models.NavigationMenu)
class NavigationMenuAdmin(admin.ModelAdmin):
    inlines = [NavigationItemInline]


for model in (models.FAQ, models.TeamMember, models.Partner, models.JobOpening, models.MediaAsset):
    admin.site.register(model)
