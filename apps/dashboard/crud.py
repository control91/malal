"""
Declarative CRUD engine for the back-office.

A ``Resource`` describes how a model is listed, filtered, edited and audited.
Views are generated from it and every one of them checks the resource's
capability (``<area>.view`` / ``<area>.edit``) server-side.
"""

from __future__ import annotations

import csv
from dataclasses import dataclass, field
from typing import Any

from django.contrib import messages
from django.core.exceptions import PermissionDenied
from django.core.paginator import Paginator
from django.db import models, transaction
from django.db.models import ProtectedError, Q
from django.forms import inlineformset_factory, modelform_factory
from django.http import HttpResponse
from django.shortcuts import get_object_or_404, redirect, render
from django.urls import path, reverse
from django.utils import timezone
from django.views.decorators.http import require_POST

from apps.accounts.roles import has_capability
from apps.audit.models import AuditLog
from apps.audit.services import form_changes, record

from .forms import DashboardModelForm
from .permissions import capability_required


@dataclass
class Column:
    attr: str
    label: str
    kind: str = "text"  # text | title | badge | bool | date | datetime | image | money | muted
    sort: str | None = None


@dataclass
class Inline:
    model: type[models.Model]
    fields: list[str]
    label: str
    extra: int = 0
    fk_name: str | None = None
    help: str = ""
    layout: str = "stack"  # stack | grid

    def formset_class(self, parent):
        return inlineformset_factory(
            parent, self.model, form=DashboardModelForm, fields=self.fields, extra=self.extra,
            can_delete=True, fk_name=self.fk_name,
        )


@dataclass
class Resource:
    slug: str
    model: type[models.Model]
    area: str
    label: str
    label_plural: str
    icon: str = "file"
    group: str = "Contenus"
    columns: list[Column] = field(default_factory=list)
    search_fields: list[str] = field(default_factory=list)
    filters: list[str] = field(default_factory=list)
    fields: list[str] | None = None
    fieldsets: list[tuple[str, list[str]]] | None = None
    form_class: type | None = None
    inlines: list[Inline] = field(default_factory=list)
    ordering: list[str] | None = None
    select_related: list[str] = field(default_factory=list)
    publish_field: str | None = None  # "status" (PublishStatus) or a boolean field name
    can_create: bool = True
    can_edit: bool = True
    can_delete: bool = True
    exportable: list[str] = field(default_factory=list)
    show_in_menu: bool = True
    list_template: str = "dashboard/crud/list.html"
    form_template: str = "dashboard/crud/form.html"
    detail_url_name: str | None = None
    description: str = ""
    per_page: int = 25

    # -- capabilities ----------------------------------------------------
    @property
    def view_cap(self) -> str:
        return f"{self.area}.view"

    @property
    def edit_cap(self) -> str:
        return f"{self.area}.edit"

    # -- urls --------------------------------------------------------------
    def url(self, name: str, *args) -> str:
        return reverse(f"dashboard:{self.slug}_{name}", args=args)

    def obj_url(self, obj) -> str:
        if self.detail_url_name:
            return reverse(self.detail_url_name, args=[obj.pk])
        return self.url("update", obj.pk) if self.can_edit else ""

    # -- hooks -------------------------------------------------------------
    def get_queryset(self, request):
        qs = self.model._default_manager.all()
        if self.select_related:
            qs = qs.select_related(*self.select_related)
        return qs.order_by(*self.ordering) if self.ordering else qs

    def get_form_class(self, request, obj=None):
        if self.form_class:
            return self.form_class
        fields = self.fields or [f for _title, names in (self.fieldsets or []) for f in names] or "__all__"
        return modelform_factory(self.model, form=DashboardModelForm, fields=fields)

    def get_form_kwargs(self, request, obj=None) -> dict[str, Any]:
        return {}

    def get_initial(self, request) -> dict[str, Any]:
        return {key: value for key, value in request.GET.items() if key in {f.name for f in self.model._meta.fields}}

    def before_save(self, request, obj, form, created: bool) -> None:
        if created and hasattr(obj, "author_id") and not obj.author_id and request.user.is_authenticated:
            obj.author = request.user
        if created and hasattr(obj, "uploaded_by_id") and not obj.uploaded_by_id:
            obj.uploaded_by = request.user

    def after_save(self, request, obj, form, created: bool) -> None:
        pass

    def success_url(self, request, obj) -> str:
        if request.POST.get("_continue"):
            return self.url("update", obj.pk)
        if nxt := request.POST.get("next") or request.GET.get("next"):
            if nxt.startswith("/") and not nxt.startswith("//"):
                return nxt
        return self.url("list")

    def can_delete_obj(self, obj) -> bool:
        return self.can_delete and not getattr(obj, "is_system", False)

    def is_published(self, obj) -> bool | None:
        if not self.publish_field:
            return None
        value = getattr(obj, self.publish_field)
        return value == "published" if self.publish_field == "status" else bool(value)

    def view_on_site(self, obj) -> str:
        if hasattr(obj, "get_absolute_url") and self.is_published(obj) is not False:
            try:
                return obj.get_absolute_url()
            except Exception:
                return ""
        return ""

    # -- urls registration -------------------------------------------------
    def urlpatterns(self):
        patterns = [path(f"{self.slug}/", self._wrap(list_view, self.view_cap), name=f"{self.slug}_list")]
        if self.can_create:
            patterns.append(path(f"{self.slug}/nouveau/", self._wrap(form_view, self.edit_cap), name=f"{self.slug}_create"))
        if self.can_edit:
            patterns.append(path(f"{self.slug}/<int:pk>/", self._wrap(form_view, self.view_cap), name=f"{self.slug}_update"))
        if self.can_delete:
            patterns.append(path(f"{self.slug}/<int:pk>/supprimer/", self._wrap(delete_view, self.edit_cap), name=f"{self.slug}_delete"))
        if self.publish_field:
            patterns.append(path(f"{self.slug}/<int:pk>/publication/", self._wrap(toggle_publish_view, self.edit_cap), name=f"{self.slug}_toggle"))
        if self.exportable:
            patterns.append(path(f"{self.slug}/export.csv", self._wrap(export_view, self.view_cap), name=f"{self.slug}_export"))
        return patterns

    def _wrap(self, view, capability):
        resource = self

        def wrapped(request, *args, **kwargs):
            return view(request, resource, *args, **kwargs)

        wrapped.__name__ = f"{self.slug}_{view.__name__}"
        return capability_required(capability)(wrapped)


registry: dict[str, Resource] = {}


def register(resource: Resource) -> Resource:
    registry[resource.slug] = resource
    return resource


# ---------------------------------------------------------------------------
# Generic views
# ---------------------------------------------------------------------------
def _filter_specs(resource: Resource, request):
    specs = []
    for name in resource.filters:
        model_field = resource.model._meta.get_field(name)
        if isinstance(model_field, models.BooleanField):
            choices = [("1", "Oui"), ("0", "Non")]
        elif model_field.choices:
            choices = [(str(k), v) for k, v in model_field.choices if k != ""]
        elif isinstance(model_field, models.ForeignKey):
            choices = [(str(o.pk), str(o)) for o in model_field.related_model._default_manager.all()[:200]]
        else:
            continue
        specs.append({"name": name, "label": model_field.verbose_name, "choices": choices, "value": request.GET.get(name, "")})
    return specs


def apply_filters(resource: Resource, request, qs):
    q = request.GET.get("q", "").strip()[:100]
    if q and resource.search_fields:
        condition = Q()
        for name in resource.search_fields:
            condition |= Q(**{f"{name}__icontains": q})
        qs = qs.filter(condition)
    for name in resource.filters:
        value = request.GET.get(name, "")
        if value == "":
            continue
        model_field = resource.model._meta.get_field(name)
        if isinstance(model_field, models.BooleanField):
            qs = qs.filter(**{name: value == "1"})
        elif isinstance(model_field, models.ForeignKey):
            if value.isdigit():
                qs = qs.filter(**{f"{name}_id": int(value)})
        else:
            qs = qs.filter(**{name: value})
    sort = request.GET.get("tri", "")
    allowed = {c.sort for c in resource.columns if c.sort}
    if sort.lstrip("-") in allowed:
        qs = qs.order_by(sort)
    return qs, q


def list_view(request, resource: Resource):
    qs, q = apply_filters(resource, request, resource.get_queryset(request))
    page = Paginator(qs, resource.per_page).get_page(request.GET.get("page"))
    can_edit = has_capability(request.user, resource.edit_cap)
    context = {
        "resource": resource,
        "page_obj": page,
        "rows": [{"obj": obj, "url": resource.obj_url(obj), "published": resource.is_published(obj), "can_delete": resource.can_delete_obj(obj)} for obj in page],
        "filters": _filter_specs(resource, request),
        "q": q,
        "can_edit": can_edit,
        "sort": request.GET.get("tri", ""),
        "has_filters": bool(q or any(request.GET.get(n) for n in resource.filters)),
    }
    template = "dashboard/crud/_table.html" if request.headers.get("HX-Request") and request.headers.get("HX-Target") == "crud-table" else resource.list_template
    return render(request, template, context)


def form_view(request, resource: Resource, pk: int | None = None):
    obj = get_object_or_404(resource.get_queryset(request), pk=pk) if pk else None
    created = obj is None
    can_edit = has_capability(request.user, resource.edit_cap)
    if request.method == "POST" and not can_edit:
        raise PermissionDenied
    form_class = resource.get_form_class(request, obj)
    kwargs = resource.get_form_kwargs(request, obj)
    was_published = resource.is_published(obj) if obj else False

    if request.method == "POST":
        form = form_class(request.POST, request.FILES, instance=obj, **kwargs)
        target = form.instance
        formsets = [(inline, inline.formset_class(resource.model)(request.POST, request.FILES, instance=target, prefix=f"inline{i}")) for i, inline in enumerate(resource.inlines)]
        if form.is_valid() and all(fs.is_valid() for _i, fs in formsets):
            with transaction.atomic():
                instance = form.save(commit=False)
                resource.before_save(request, instance, form, created)
                instance.save()
                form.save_m2m()
                inline_changes = {}
                for inline, fs in formsets:
                    fs.instance = instance
                    fs.save()
                    if fs.has_changed():
                        inline_changes[str(inline.label)] = f"{len(fs.new_objects)} ajout(s), {len(fs.changed_objects)} modif., {len(fs.deleted_objects)} suppr."
                resource.after_save(request, instance, form, created)
                changes = form_changes(form) | inline_changes
                record(request, AuditLog.Action.CREATE if created else AuditLog.Action.UPDATE, instance, changes=changes)
                now_published = resource.is_published(instance)
                if resource.publish_field and now_published != was_published:
                    record(request, AuditLog.Action.PUBLISH if now_published else AuditLog.Action.UNPUBLISH, instance)
            messages.success(request, f"{resource.label.capitalize()} « {instance} » {'créé(e)' if created else 'enregistré(e)'}.")
            return redirect(resource.success_url(request, instance))
        messages.error(request, "Le formulaire contient des erreurs. Vérifiez les champs signalés.")
    else:
        form = form_class(instance=obj, initial=resource.get_initial(request) if created else None, **kwargs)
        formsets = [(inline, inline.formset_class(resource.model)(instance=obj or resource.model(), prefix=f"inline{i}")) for i, inline in enumerate(resource.inlines)]

    if not can_edit:
        for field_obj in form.fields.values():
            field_obj.disabled = True

    fieldsets = resource.fieldsets or [("", list(form.fields.keys()))]
    known = {name for _t, names in fieldsets for name in names}
    leftovers = [name for name in form.fields if name not in known]
    if leftovers:
        fieldsets = [*fieldsets, ("Autres champs", leftovers)]
    context = {
        "resource": resource,
        "object": obj,
        "form": form,
        "fieldsets": [(title, [form[name] for name in names if name in form.fields]) for title, names in fieldsets],
        "formsets": formsets,
        "can_edit": can_edit,
        "view_on_site": resource.view_on_site(obj) if obj else "",
        "published": resource.is_published(obj) if obj else None,
        "can_delete": bool(obj) and resource.can_delete_obj(obj) and can_edit,
    }
    return render(request, resource.form_template, context)


@require_POST
def delete_view(request, resource: Resource, pk: int):
    obj = get_object_or_404(resource.get_queryset(request), pk=pk)
    if not resource.can_delete_obj(obj):
        messages.error(request, "Cet élément ne peut pas être supprimé.")
        return redirect(resource.url("list"))
    label = str(obj)
    try:
        with transaction.atomic():
            record(request, AuditLog.Action.DELETE, obj, object_repr=label)
            obj.delete()
    except ProtectedError:
        messages.error(request, f"« {label} » est utilisé ailleurs et ne peut pas être supprimé.")
        return redirect(resource.url("list"))
    messages.success(request, f"« {label} » a été supprimé.")
    return redirect(request.POST.get("next") if (request.POST.get("next") or "").startswith("/") else resource.url("list"))


@require_POST
def toggle_publish_view(request, resource: Resource, pk: int):
    obj = get_object_or_404(resource.get_queryset(request), pk=pk)
    publish = not resource.is_published(obj)
    if resource.publish_field == "status":
        obj.status = "published" if publish else "draft"
        if publish and hasattr(obj, "published_at") and not obj.published_at:
            obj.published_at = timezone.now()
    else:
        setattr(obj, resource.publish_field, publish)
    try:
        obj.full_clean()
    except Exception as exc:  # e.g. missing consent on a case study
        messages.error(request, f"Publication impossible : {'; '.join(sum(getattr(exc, 'message_dict', {}).values(), [])) or exc}")
        return redirect(request.META.get("HTTP_REFERER") or resource.url("list"))
    obj.save()
    record(request, AuditLog.Action.PUBLISH if publish else AuditLog.Action.UNPUBLISH, obj)
    messages.success(request, f"« {obj} » {'publié' if publish else 'dépublié'}.")
    if request.headers.get("HX-Request"):
        response = HttpResponse(status=204)
        response["HX-Refresh"] = "true"
        return response
    referer = request.META.get("HTTP_REFERER", "")
    return redirect(referer if referer.startswith(request.build_absolute_uri("/")) else resource.url("list"))


def export_view(request, resource: Resource):
    qs, _q = apply_filters(resource, request, resource.get_queryset(request))
    response = HttpResponse(content_type="text/csv; charset=utf-8")
    response["Content-Disposition"] = f'attachment; filename="{resource.slug}-{timezone.localdate():%Y%m%d}.csv"'
    response.write("﻿")  # Excel-friendly UTF-8
    writer = csv.writer(response, delimiter=";")
    model_fields = {f.name: f for f in resource.model._meta.fields}
    writer.writerow([str(model_fields[n].verbose_name) if n in model_fields else n for n in resource.exportable])
    for obj in qs.iterator(chunk_size=500) if not resource.select_related else qs:
        row = []
        for name in resource.exportable:
            display = getattr(obj, f"get_{name}_display", None)
            value = display() if callable(display) else getattr(obj, name, "")
            if hasattr(value, "isoformat"):
                value = timezone.localtime(value).strftime("%Y-%m-%d %H:%M") if hasattr(value, "hour") else value.isoformat()
            text = "" if value is None else str(value)
            if text[:1] in ("=", "+", "-", "@"):  # CSV formula injection guard
                text = "'" + text
            row.append(text)
        writer.writerow(row)
    record(request, AuditLog.Action.EXPORT, None, object_repr=f"Export CSV {resource.label_plural} ({qs.count()} lignes)")
    return response
