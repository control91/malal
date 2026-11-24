<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\CaseStudy;
use App\Models\JobOpening;
use App\Models\Page;
use App\Models\Product;
use App\Models\ServiceCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /connexion', 'Disallow: /merci', 'Disallow: /newsletter/', '', 'Sitemap: '.route('sitemap')];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', 1800, function () {
            $urls = collect(['home', 'solutions.index', 'pricing', 'ai', 'blog.index', 'cases.index', 'contact', 'quote', 'demo', 'faq', 'careers'])
                ->map(fn ($name) => ['loc' => route($name), 'lastmod' => null]);
            $add = fn ($items, $url) => $items->each(fn ($m) => $urls->push(['loc' => $url($m), 'lastmod' => $m->updated_at?->toAtomString()]));

            $add(Page::published()->where('noindex', false)->where('slug', '!=', 'accueil')->get(), fn ($p) => $p->url());
            $add(ServiceCategory::where('is_published', true)->where('noindex', false)->get(), fn ($c) => $c->url());
            $add(Product::published()->where('noindex', false)->get(), fn ($p) => $p->url());
            $add(BlogArticle::published()->where('noindex', false)->get(), fn ($a) => $a->url());
            $add(CaseStudy::published()->where('noindex', false)->get(), fn ($c) => $c->url());
            $add(JobOpening::open()->get(), fn ($j) => route('careers.show', $j->slug));

            $body = $urls->map(fn ($u) => '<url><loc>'.e($u['loc']).'</loc>'.($u['lastmod'] ? '<lastmod>'.$u['lastmod'].'</lastmod>' : '').'</url>')->implode('');

            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
