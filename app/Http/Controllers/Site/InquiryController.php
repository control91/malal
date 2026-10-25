<?php

namespace App\Http\Controllers\Site;

use App\Enums\Budget;
use App\Enums\InquiryKind;
use App\Enums\Timeline;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicInquiryRequest;
use App\Models\PricingPlan;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Services\InquiryService;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function __construct(private InquiryService $inquiries) {}

    public function contact(Request $request): Response
    {
        return Inertia::render('Site/Contact', [
            'initial' => ['kind' => in_array($request->query('type'), InquiryKind::values(), true) ? $request->query('type') : 'contact'],
            'kinds' => [
                ['value' => 'contact', 'label' => 'Question générale'], ['value' => 'quote', 'label' => 'Projet / devis'],
                ['value' => 'partnership', 'label' => 'Partenariat'], ['value' => 'support', 'label' => 'Support'], ['value' => 'career', 'label' => 'Candidature spontanée'],
            ],
            'categories' => $this->categories(),
        ])->with('seo', Seo::make('Contact', 'Parlez de votre projet Cloud, SaaS ou IA avec un expert.'));
    }

    public function quote(Request $request): Response
    {
        $initial = [];
        if ($plan = PricingPlan::where('is_published', true)->with('group')->find((int) $request->query('offre'))) {
            $initial = ['pricing_plan_id' => $plan->id, 'subject' => "Devis — {$plan}", 'product_id' => $plan->group?->product_id, 'service_category_id' => $plan->group?->service_category_id];
        }
        if ($request->query('solution') && $product = Product::published()->where('slug', $request->query('solution'))->first()) {
            $initial = ['product_id' => $product->id, 'service_category_id' => $product->service_category_id, 'subject' => "Devis — {$product->name}"] + $initial;
        }
        if ($request->query('domaine') && $category = ServiceCategory::where('is_published', true)->where('slug', $request->query('domaine'))->first()) {
            $initial += ['service_category_id' => $category->id, 'subject' => "Projet {$category->name}"];
        }

        return Inertia::render('Site/Quote', [
            'initial' => $initial,
            'categories' => $this->categories(),
            'products' => $this->products(),
            'budgets' => Budget::options(),
            'timelines' => Timeline::options(),
        ])->with('seo', Seo::make('Demander un devis', 'Décrivez votre besoin : nous revenons vers vous avec une proposition adaptée.'));
    }

    public function demo(Request $request): Response
    {
        $product = Product::published()->where('demo_enabled', true)->where('slug', (string) $request->query('solution'))->first();

        return Inertia::render('Site/Demo', [
            'initial' => ['product_id' => $product?->id],
            'products' => $this->products(demoOnly: true),
        ])->with('seo', Seo::make('Demander une démonstration', 'Planifiez une démonstration personnalisée de nos solutions.'));
    }

    public function storeContact(PublicInquiryRequest $request): RedirectResponse
    {
        return $this->store($request, InquiryKind::from($request->validated('kind')));
    }

    public function storeQuote(PublicInquiryRequest $request): RedirectResponse
    {
        return $this->store($request, InquiryKind::Quote);
    }

    public function storeDemo(PublicInquiryRequest $request): RedirectResponse
    {
        return $this->store($request, InquiryKind::Demo);
    }

    public function thanks(Request $request): Response|RedirectResponse
    {
        $reference = $request->session()->get('inquiry_reference');
        if (! $reference) {
            return redirect()->route('home');
        }

        return Inertia::render('Site/Thanks', ['reference' => $reference])->with('seo', Seo::make('Merci', noindex: true));
    }

    private function store(PublicInquiryRequest $request, InquiryKind $kind): RedirectResponse
    {
        $inquiry = $this->inquiries->register($request, $request->inquiryData(), $kind);

        return redirect()->route('thanks')->with('inquiry_reference', $inquiry->reference);
    }

    private function categories()
    {
        return ServiceCategory::where('is_published', true)->ordered()->get(['id', 'name'])->map(fn ($c) => ['value' => $c->id, 'label' => $c->name]);
    }

    private function products(bool $demoOnly = false)
    {
        return Product::published()->when($demoOnly, fn ($q) => $q->where('demo_enabled', true))->ordered()->get(['id', 'name'])->map(fn ($p) => ['value' => $p->id, 'label' => $p->name]);
    }
}
