<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\Faq;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Institutional base content — safe for production. Idempotent: existing rows
 * are left untouched (editors' changes are never overwritten) unless $force.
 * Data lives in database/seeders/data/site-content.json.
 */
class SiteContentSeeder extends Seeder
{
    public bool $force = false;

    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/site-content.json'), true, flags: JSON_THROW_ON_ERROR);

        // The cached row may belong to a database that has since been reset.
        Cache::forget(SiteSetting::CACHE_KEY);
        $site = SiteSetting::current();
        if (! $site->meta_description) {
            $site->update([
                'meta_description' => 'Cloud, logiciels SaaS et Intelligence Artificielle pour les entreprises africaines. Basés à Conakry, Guinée.',
                'footer_text' => 'Cloud, SaaS et Intelligence Artificielle pour accélérer la transformation numérique des organisations africaines.',
            ]);
        }

        foreach ($data['domains'] as $order => $domain) {
            $services = $domain['services'];
            unset($domain['services']);
            $category = $this->upsert(ServiceCategory::class, ['slug' => $domain['slug']], $domain + ['order' => $order * 10]);
            foreach ($services as $i => $service) {
                $this->upsert(Service::class, ['service_category_id' => $category->id, 'slug' => $service['slug']], $service + ['order' => $i * 10]);
            }
        }

        $this->page('accueil', 'Accueil', $data['home'], ['template' => 'home', 'is_system' => true]);
        $this->page('entreprise', 'Entreprise', $data['about'], ['is_system' => true, 'eyebrow' => 'À propos',
            'intro' => 'Une entreprise technologique guinéenne qui construit des services Cloud, SaaS et IA pour l\'Afrique.']);
        foreach ($data['legal'] as $slug => $page) {
            $this->upsert(Page::class, ['slug' => $slug], $page + ['template' => $slug === 'ressources' ? 'standard' : 'legal', 'status' => 'published', 'is_system' => true]);
        }

        foreach ($data['faqs'] as $order => $faq) {
            $category = $faq['category'] ? ServiceCategory::where('slug', $faq['category'])->value('id') : null;
            $this->upsert(Faq::class, ['question' => $faq['question']], ['answer' => $faq['answer'], 'service_category_id' => $category, 'order' => $order * 10]);
        }

        foreach ([['actualites', 'Actualités'], ['guides', 'Guides'], ['cloud', 'Cloud'], ['ia', 'Intelligence Artificielle']] as $order => [$slug, $name]) {
            $this->upsert(BlogCategory::class, ['slug' => $slug], ['name' => $name, 'order' => $order]);
        }

        $this->menus($data['menus']);
    }

    private function upsert(string $model, array $lookup, array $values)
    {
        $values = array_diff_key($values, $lookup);
        $record = $model::where($lookup)->first();
        if (! $record) {
            return $model::create($lookup + $values);
        }
        if ($this->force) {
            $record->update($values);
        }

        return $record;
    }

    private function page(string $slug, string $title, array $sections, array $extra): void
    {
        $exists = Page::where('slug', $slug)->exists();
        $page = $this->upsert(Page::class, ['slug' => $slug], ['title' => $title, 'status' => 'published'] + $extra);
        if ($exists && ! $this->force) {
            return;
        }
        $page->sections()->delete();
        foreach ($sections as $order => $section) {
            $items = $section['items'] ?? [];
            unset($section['items']);
            $created = $page->sections()->create($section + ['order' => $order * 10]);
            foreach ($items as $i => $item) {
                $created->items()->create(array_map(fn ($v) => $v ?? '', $item) + ['order' => $i * 10]);
            }
        }
    }

    private function menus(array $menus): void
    {
        foreach ($menus as $key => $menu) {
            $record = NavigationMenu::firstWhere('key', $key);
            if ($record && ! $this->force) {
                continue;
            }
            $record ??= NavigationMenu::create(['key' => $key, 'title' => $menu['title']]);
            $record->update(['title' => $menu['title']]);
            $record->items()->delete();
            foreach ($menu['items'] as $order => $item) {
                $parent = $record->items()->create([
                    'label' => $item['label'], 'url' => $item['url'], 'description' => $item['description'] ?? '',
                    'style' => $item['style'] ?? 'link', 'order' => $order * 10,
                ]);
                foreach ($item['children'] ?? [] as $i => $child) {
                    NavigationItem::create($child + ['navigation_menu_id' => $record->id, 'parent_id' => $parent->id, 'order' => $i * 10]);
                }
            }
        }
    }
}
