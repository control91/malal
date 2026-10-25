<?php

namespace App\Admin;

use App\Admin\Resources as R;
use App\Models\User;

final class Registry
{
    /** @var array<int, class-string<resource>> Order = order in the sidebar. */
    public const RESOURCES = [
        R\InquiryResource::class, R\LeadResource::class, R\NewsletterResource::class,
        R\PageResource::class, R\SectionResource::class, R\MenuResource::class, R\FaqResource::class, R\TeamResource::class,
        R\PartnerResource::class, R\JobResource::class, R\MediaResource::class,
        R\CategoryResource::class, R\ServiceResource::class, R\ProductResource::class, R\PricingGroupResource::class, R\PlanResource::class, R\StatusResource::class,
        R\ArticleResource::class, R\BlogCategoryResource::class, R\TagResource::class, R\CaseStudyResource::class, R\TestimonialResource::class,
        R\UserResource::class, R\AuditResource::class,
    ];

    public const GROUPS = ['Commercial' => 'briefcase', 'Site' => 'panels-top-left', 'Catalogue' => 'package', 'Éditorial' => 'notebook-pen', 'Administration' => 'shield'];

    /** @return class-string<resource>|null */
    public static function find(string $slug): ?string
    {
        foreach (self::RESOURCES as $resource) {
            if ($resource::$slug === $slug) {
                return $resource;
            }
        }

        return null;
    }

    /** Sidebar entries the user may see (UI only — controllers re-check). */
    public static function navigation(User $user): array
    {
        $groups = array_fill_keys(array_keys(self::GROUPS), []);
        foreach (self::RESOURCES as $resource) {
            if ($resource::$showInMenu && $resource::allows($user)) {
                $groups[$resource::$group][] = ['label' => $resource::$labelPlural, 'icon' => $resource::$icon, 'url' => route('admin.resources.index', $resource::$slug)];
            }
        }
        if ($user->canDo('crm.view')) {
            array_splice($groups['Commercial'], 1, 0, [['label' => 'Pipeline', 'icon' => 'kanban', 'url' => route('admin.pipeline')]]);
            array_splice($groups['Commercial'], 2, 0, [['label' => 'Relances', 'icon' => 'calendar-check', 'url' => route('admin.followups')]]);
        }
        if ($user->canDo('analytics.view')) {
            array_unshift($groups['Administration'], ['label' => 'Statistiques', 'icon' => 'chart-line', 'url' => route('admin.analytics')]);
        }
        if ($user->canDo('settings.view')) {
            array_splice($groups['Administration'], 1, 0, [['label' => 'Configuration', 'icon' => 'settings', 'url' => route('admin.settings.edit')]]);
        }

        return array_values(array_filter(array_map(fn ($title, $items) => $items ? ['title' => $title, 'icon' => self::GROUPS[$title], 'items' => $items] : null, array_keys($groups), $groups)));
    }
}
