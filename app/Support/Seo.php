<?php

namespace App\Support;

use App\Models\SiteSetting;

/** Page metadata rendered server-side in the root Blade view (title, description, Open Graph, JSON-LD). */
final class Seo
{
    public static function make(string $title = '', ?string $description = '', ?string $image = null, ?object $model = null, string $type = 'website', bool $noindex = false, array $jsonLd = [], ?string $publishedTime = null): array
    {
        if ($model) {
            $title = ($model->meta_title ?? '') ?: $title;
            $description = ($model->meta_description ?? '') ?: $description;
            if (! empty($model->og_image)) {
                $image = Images::url($model->og_image);
            }
            $noindex = $noindex || (bool) ($model->noindex ?? false);
        }

        return [
            'title' => $title,
            'description' => mb_substr(strip_tags((string) $description), 0, 170),
            'image' => $image ? self::absolute($image) : null,
            'canonical' => url()->current(),
            'type' => $type,
            'noindex' => $noindex,
            'json_ld' => $jsonLd,
            'published_time' => $publishedTime,
        ];
    }

    public static function absolute(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return str_starts_with($url, 'http') ? $url : rtrim(config('app.url'), '/').'/'.ltrim($url, '/');
    }

    public static function organization(SiteSetting $site): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site->company_name,
            'legalName' => $site->legal_name ?: null,
            'url' => config('app.url'),
            'description' => $site->tagline,
            'logo' => self::absolute($site->logo ? Images::url($site->logo) : '/img/logo-square-512.png'),
            'email' => $site->email_contact ?: null,
            'telephone' => $site->phone ?: null,
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $site->city, 'addressCountry' => $site->country],
            'sameAs' => array_column($site->socialLinks(), 'url') ?: null,
        ]);
    }

    /** @param array<int, array{0: string, 1: string}> $items */
    public static function breadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($item, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => self::absolute($item[1]),
            ], $items, array_keys($items)),
        ];
    }
}
