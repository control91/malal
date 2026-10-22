"""Articles, news and resources."""

import math
import re

from django.conf import settings
from django.db import models
from django.urls import reverse
from django.utils import timezone
from django.utils.html import strip_tags
from django.utils.translation import gettext_lazy as _

from apps.core.fields import OptimizedImageField, RichTextField
from apps.core.models import OrderedModel, PublishStatus, SeoModel, TimeStampedModel


class BlogCategory(OrderedModel):
    name = models.CharField(_("nom"), max_length=80)
    slug = models.SlugField(_("slug"), max_length=80, unique=True)
    description = models.TextField(_("description"), blank=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("catégorie d'article")
        verbose_name_plural = _("catégories d'articles")

    def __str__(self) -> str:
        return self.name

    def get_absolute_url(self) -> str:
        return reverse("blog:category", kwargs={"slug": self.slug})


class BlogTag(models.Model):
    name = models.CharField(_("nom"), max_length=60)
    slug = models.SlugField(_("slug"), max_length=60, unique=True)

    class Meta:
        verbose_name = _("étiquette")
        verbose_name_plural = _("étiquettes")
        ordering = ["name"]

    def __str__(self) -> str:
        return self.name

    def get_absolute_url(self) -> str:
        return reverse("blog:tag", kwargs={"slug": self.slug})


class ArticleQuerySet(models.QuerySet):
    def published(self):
        """Published and due: a future ``published_at`` means a scheduled article."""
        return self.filter(status=PublishStatus.PUBLISHED, published_at__lte=timezone.now())


class BlogArticle(TimeStampedModel, SeoModel):
    title = models.CharField(_("titre"), max_length=200)
    slug = models.SlugField(_("slug"), max_length=200, unique=True)
    excerpt = models.TextField(_("chapô"), max_length=400, blank=True)
    body = RichTextField(_("contenu"))
    cover = OptimizedImageField(_("image de couverture"), upload_to="blog/%Y/", blank=True)
    cover_alt = models.CharField(_("texte alternatif de la couverture"), max_length=200, blank=True)
    category = models.ForeignKey(BlogCategory, verbose_name=_("catégorie"), null=True, blank=True, on_delete=models.SET_NULL, related_name="articles")
    tags = models.ManyToManyField(BlogTag, verbose_name=_("étiquettes"), blank=True, related_name="articles")
    author = models.ForeignKey(settings.AUTH_USER_MODEL, verbose_name=_("auteur"), null=True, blank=True, on_delete=models.SET_NULL, related_name="articles")
    status = models.CharField(_("statut"), max_length=20, choices=PublishStatus.choices, default=PublishStatus.DRAFT, db_index=True)
    published_at = models.DateTimeField(
        _("date de publication"), null=True, blank=True, db_index=True,
        help_text=_("Une date future programme la publication."),
    )
    is_featured = models.BooleanField(_("à la une"), default=False)

    objects = ArticleQuerySet.as_manager()

    class Meta:
        verbose_name = _("article")
        verbose_name_plural = _("articles")
        ordering = ["-published_at", "-created_at"]

    def __str__(self) -> str:
        return self.title

    def save(self, *args, **kwargs):
        if self.status == PublishStatus.PUBLISHED and not self.published_at:
            self.published_at = timezone.now()
        super().save(*args, **kwargs)

    def get_absolute_url(self) -> str:
        return reverse("blog:detail", kwargs={"slug": self.slug})

    @property
    def is_live(self) -> bool:
        return self.status == PublishStatus.PUBLISHED and bool(self.published_at) and self.published_at <= timezone.now()

    @property
    def is_scheduled(self) -> bool:
        return self.status == PublishStatus.PUBLISHED and bool(self.published_at) and self.published_at > timezone.now()

    @property
    def display_status(self) -> str:
        return str(_("Programmé")) if self.is_scheduled else self.get_status_display()

    @property
    def reading_time(self) -> int:
        words = len(re.findall(r"\w+", strip_tags(self.body or "")))
        return max(1, math.ceil(words / 220))

    def related(self, limit: int = 3):
        qs = BlogArticle.objects.published().exclude(pk=self.pk)
        if self.category_id:
            qs = qs.filter(models.Q(category_id=self.category_id) | models.Q(tags__in=self.tags.all())).distinct()
        return qs.select_related("category")[:limit]
