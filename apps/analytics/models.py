"""
First-party, cookie-less audience measurement.

Only daily aggregates are stored. A visitor is a salted hash of
(IP, user-agent, day): it cannot be reversed nor linked across days.
"""

from django.db import models
from django.utils.translation import gettext_lazy as _


class DailyPageView(models.Model):
    date = models.DateField(_("jour"), db_index=True)
    path = models.CharField(_("page"), max_length=255)
    views = models.PositiveIntegerField(_("vues"), default=0)

    class Meta:
        verbose_name = _("vues par page")
        verbose_name_plural = _("vues par page")
        constraints = [models.UniqueConstraint(fields=["date", "path"], name="unique_pageview_day_path")]

    def __str__(self) -> str:
        return f"{self.date} {self.path} ({self.views})"


class DailyVisitor(models.Model):
    date = models.DateField(_("jour"), db_index=True)
    visitor = models.CharField(max_length=32)

    class Meta:
        verbose_name = _("visiteur unique")
        verbose_name_plural = _("visiteurs uniques")
        constraints = [models.UniqueConstraint(fields=["date", "visitor"], name="unique_visitor_day")]

    def __str__(self) -> str:
        return f"{self.date} {self.visitor[:8]}"
