@php
    $props = $page['props'] ?? [];
    $site = $props['site'] ?? [];
    $seo = $props['seo'] ?? null;
    $isAdmin = str_starts_with($page['component'] ?? '', 'Admin/') || str_starts_with($page['component'] ?? '', 'Auth/');
    $companyName = $site['company_name'] ?? config('app.name');
    $title = $seo['title'] ?? '';
    $fullTitle = $title === '' || $title === $companyName ? $companyName.' — '.($site['tagline'] ?? '') : $title.' · '.$companyName;
    $description = ($seo['description'] ?? '') ?: ($site['tagline'] ?? '');
    $image = ($seo['image'] ?? null) ?: url('/img/logo-square-512.png');
    $colors = $site['colors'] ?? ['primary' => '#1167B1', 'accent' => '#22D3EE', 'ink' => '#0A1330'];
    $nonce = app('csp-nonce');
@endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ $isAdmin ? ($title ?: 'Administration').' · '.$companyName : $fullTitle }}</title>
    @if ($isAdmin)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="description" content="{{ $description }}">
        @if ($seo['canonical'] ?? null)
            <link rel="canonical" href="{{ $seo['canonical'] }}">
        @endif
        @if ($seo['noindex'] ?? false)
            <meta name="robots" content="noindex, nofollow">
        @endif
        <meta property="og:site_name" content="{{ $companyName }}">
        <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
        <meta property="og:title" content="{{ $title ?: $companyName }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:locale" content="fr_GN">
        @if ($seo['canonical'] ?? null)
            <meta property="og:url" content="{{ $seo['canonical'] }}">
        @endif
        <meta property="og:image" content="{{ $image }}">
        @if ($seo['published_time'] ?? null)
            <meta property="article:published_time" content="{{ $seo['published_time'] }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title ?: $companyName }}">
        <meta name="twitter:description" content="{{ $description }}">
        @foreach ($seo['json_ld'] ?? [] as $block)
            <script type="application/ld+json"
                    nonce="{{ $nonce }}">{!! json_encode($block, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endforeach
    @endif
    <meta name="theme-color" content="#05070d">
    <link rel="icon" href="/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/img/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
    @unless ($isAdmin)
        <link rel="preload" href="/fonts/nunito-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    @endunless
    <style>:root {
            --brand-primary: {{ $colors['primary'] }};
            --brand-accent: {{ $colors['accent'] }};
            --brand-ink: {{ $colors['ink'] }}
        }</style>
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="{{ $isAdmin ? 'h-full bg-surface text-slate-700' : 'site' }}">
@inertia
</body>
</html>
