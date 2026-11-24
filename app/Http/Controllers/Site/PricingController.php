<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\PricingGroup;
use App\Support\Present;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function index(): Response
    {
        $tables = PricingGroup::where('is_published', true)->ordered()
            ->with(['plans' => fn ($q) => $q->where('is_published', true)->with('features'), 'options', 'category'])
            ->get()->filter(fn ($g) => $g->plans->isNotEmpty())->values()
            ->map(fn ($g) => [
                'name' => $g->name, 'description' => $g->description,
                'category' => $g->category ? ['name' => $g->category->name, 'url' => $g->category->url()] : null,
                'plans' => $g->plans->map(fn ($p) => Present::plan($p))->all(),
                'rows' => $g->comparison($g->plans->all()),
                'options' => $g->options->map->only(['name', 'description', 'price_label'])->all(),
            ]);

        return Inertia::render('Site/Pricing', ['tables' => $tables])
            ->with('seo', Seo::make('Hébergement web', 'Hébergement web et offres cloud : formules mensuelles ou annuelles aux prix indicatifs, et devis sur mesure.'));
    }
}
