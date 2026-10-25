<?php

use App\Enums\PublishStatus;
use App\Models\BlogArticle;
use App\Models\CaseStudy;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ServiceCategory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => seedSite());

it('renders every public page', function (string $url, string $component) {
    $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    ['/', 'Site/Home'], ['/entreprise', 'Site/Page'], ['/expertises/cloud-computing', 'Site/Expertise'], ['/solutions', 'Site/Catalog'],
    ['/tarifs', 'Site/Pricing'], ['/blog', 'Site/Blog'], ['/realisations', 'Site/Cases'], ['/contact', 'Site/Contact'], ['/devis', 'Site/Quote'],
    ['/demo', 'Site/Demo'], ['/faq', 'Site/Faq'], ['/carrieres', 'Site/Careers'], ['/partenaires', 'Site/Partners'], ['/statut', 'Site/Status'],
    ['/mentions-legales', 'Site/Page'], ['/confidentialite', 'Site/Page'], ['/ressources', 'Site/Page'],
]);

it('serves home content from the CMS', function () {
    PageSection::whereHas('page', fn ($q) => $q->where('slug', 'accueil'))->where('kind', 'hero')->first()->update(['title' => 'Titre modifié depuis le CMS']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('sections.0.title', 'Titre modifié depuis le CMS'));
});

it('renders SEO metadata server-side', function () {
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('rel="canonical"')->toContain('property="og:title"')->toContain('application/ld+json')->toContain('<meta name="description"');
});

it('returns a branded 404 for unknown pages', function () {
    $this->get('/cette-page-nexiste-pas')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
});

it('hides draft pages from the public', function () {
    Page::create(['title' => 'Brouillon', 'slug' => 'brouillon', 'status' => PublishStatus::Draft]);
    $this->get('/brouillon')->assertNotFound();
});

it('publishes scheduled articles only when due', function () {
    $article = BlogArticle::create(['title' => 'Futur', 'slug' => 'futur', 'body' => '<p>x</p>', 'status' => 'published', 'published_at' => now()->addDays(2)]);
    expect($article->isScheduled())->toBeTrue();
    $this->get('/blog/futur')->assertNotFound();
    $article->update(['published_at' => now()->subMinute()]);
    $this->get('/blog/futur')->assertOk();
});

it('sanitizes rich text on save', function () {
    $article = BlogArticle::create(['title' => 'XSS', 'slug' => 'xss', 'body' => '<p onclick="evil()">ok</p><script>alert(1)</script><a href="javascript:x">l</a>']);
    expect($article->body)->not->toContain('<script')->not->toContain('onclick')->not->toContain('javascript:');
});

it('filters the catalog and hides unpublished solutions', function () {
    $cloud = ServiceCategory::where('slug', 'cloud-computing')->first();
    Product::create(['service_category_id' => $cloud->id, 'name' => 'Alpha Cloud', 'slug' => 'alpha', 'tagline' => 't', 'summary' => 's', 'is_published' => true]);
    Product::create(['service_category_id' => $cloud->id, 'name' => 'Hidden', 'slug' => 'hidden', 'tagline' => 't', 'summary' => 's', 'is_published' => false]);

    $this->get('/solutions?q=alpha')->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.name', 'Alpha Cloud'));
    $this->get('/solutions/hidden')->assertNotFound();
});

it('requires consent before publishing a case study', function () {
    $case = CaseStudy::create(['title' => 'Projet', 'slug' => 'projet', 'sector' => 'Banque', 'summary' => 's', 'challenge' => 'c', 'solution' => 's', 'status' => 'published']);
    $this->get('/realisations/projet')->assertNotFound();
    $case->update(['publication_consent' => true]);
    $this->get('/realisations/projet')->assertOk();
});

it('exposes robots.txt and an XML sitemap', function () {
    expect($this->get('/robots.txt')->assertOk()->getContent())->toContain('Disallow: /admin')->toContain('Sitemap:');
    expect($this->get('/sitemap.xml')->assertOk()->getContent())->toContain('/expertises/cloud-computing');
});

it('sends security headers', function () {
    $response = $this->get('/');
    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
    expect($response->headers->get('Content-Security-Policy'))->toContain("script-src 'self'")->not->toContain('unsafe-eval');
    expect($response->headers->get('X-Request-ID'))->not->toBeEmpty();
});

it('serves the read-only public API', function () {
    $this->getJson('/api/v1/expertises')->assertOk()->assertJsonCount(6, 'data');
    $this->postJson('/api/v1/expertises')->assertStatus(405);
});

it('answers the health check', function () {
    $this->get('/up')->assertOk();
});
