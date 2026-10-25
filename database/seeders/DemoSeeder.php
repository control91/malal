<?php

namespace Database\Seeders;

use App\Enums\InquiryKind;
use App\Enums\InquiryStatus;
use App\Enums\LeadStatus;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\CaseStudy;
use App\Models\Inquiry;
use App\Models\JobOpening;
use App\Models\Lead;
use App\Models\PricingGroup;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\StatusComponent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Development / demonstration data — never run in production.
 * Everything is flagged as an example (is_example, « Exemple » labels,
 * example.com emails, « [Démo] » notes). No real client, testimonial,
 * certification or performance figure is invented.
 */
class DemoSeeder extends Seeder
{
    /** @var array<int, array{role: string, email: string, password: string}> */
    public array $createdUsers = [];

    public function __construct(public bool $withUsers = false) {}

    public function run(): void
    {
        $this->call(SiteContentSeeder::class);
        $data = json_decode(file_get_contents(__DIR__.'/data/demo-content.json'), true, flags: JSON_THROW_ON_ERROR);
        $categories = ServiceCategory::pluck('id', 'slug');

        foreach ($data['products'] as $order => $item) {
            $product = Product::firstOrCreate(['slug' => $item['slug']], [
                'service_category_id' => $categories[$item['category']], 'name' => $item['name'], 'icon' => $item['icon'], 'tagline' => $item['tagline'],
                'summary' => $item['summary'], 'status' => $item['status'], 'is_example' => true, 'is_published' => true, 'is_featured' => $order < 3,
                'order' => $order * 10, 'benefits' => "Prise en main rapide\nAccessible depuis un navigateur\nSupport en français",
                'audience' => 'PME, associations et institutions (exemple)',
                'description' => "<p>Fiche d'exemple : décrivez ici la solution réelle, son fonctionnement et ses bénéfices.</p>",
            ]);
            if ($product->wasRecentlyCreated) {
                foreach ($item['features'] as $i => $feature) {
                    $product->features()->create($feature + ['order' => $i]);
                }
            }
        }

        $this->pricing($categories['cloud-computing']);
        $this->articles($data);
        $this->caseStudy($categories['developpement-sur-mesure']);
        $this->crm($categories);
        JobOpening::firstOrCreate(['slug' => 'developpeur-laravel-exemple'], [
            'title' => 'Développeur·se Laravel (offre d\'exemple)', 'department' => 'Ingénierie', 'summary' => 'Offre fictive pour illustrer la page Carrières.',
            'description' => "<p>Offre d'exemple. Missions, profil recherché et processus de recrutement à compléter.</p>", 'remote_possible' => true,
        ]);
        StatusComponent::firstOrCreate(['name' => 'Site institutionnel (exemple)'], ['description' => 'Composant d\'exemple pour la page statut.']);

        if ($this->withUsers) {
            foreach ([Role::Admin, Role::Editor, Role::Sales, Role::Support, Role::Viewer] as $role) {
                $email = "{$role->value}@example.com";
                if (User::where('email', $email)->exists()) {
                    continue;
                }
                $password = Str::password(16, symbols: false);
                (new User)->forceFill(['email' => $email, 'password' => $password, 'role' => $role, 'first_name' => explode(' ', $role->label())[0], 'is_active' => true])->save();
                $this->createdUsers[] = ['role' => $role->label(), 'email' => $email, 'password' => $password];
            }
        }
    }

    private function pricing(int $cloud): void
    {
        $group = PricingGroup::firstOrCreate(['slug' => 'hebergement-exemple'], [
            'name' => "Hébergement d'applications (exemple)", 'service_category_id' => $cloud,
            'description' => "Grille d'exemple : les montants sont fictifs et doivent être remplacés avant publication.",
        ]);
        if (! $group->wasRecentlyCreated) {
            return;
        }
        $plans = [
            ['Essentiel', 'Pour un site ou une petite application.', 250000, 2500000, false, [['Applications', '1'], ['Stockage', '10 Go'], ['Sauvegardes', 'Hebdomadaires'], ['Support', 'Email']]],
            ['Pro', 'Pour les applications métier.', 650000, 6500000, true, [['Applications', '5'], ['Stockage', '50 Go'], ['Sauvegardes', 'Quotidiennes'], ['Support', 'Email et téléphone']]],
            ['Entreprise', 'Infrastructure dédiée et accompagnement.', null, null, false, [['Applications', 'Sur mesure'], ['Stockage', 'Sur mesure'], ['Sauvegardes', 'Sur mesure'], ['Support', 'Interlocuteur dédié']]],
        ];
        foreach ($plans as $order => [$name, $description, $monthly, $yearly, $highlighted, $features]) {
            $plan = $group->plans()->create([
                'name' => $name, 'description' => $description, 'price_monthly' => $monthly, 'price_yearly' => $yearly, 'price_on_request' => $monthly === null,
                'is_highlighted' => $highlighted, 'order' => $order, 'price_note' => "Montant d'exemple, HT",
            ]);
            foreach ($features as $i => [$label, $value]) {
                $plan->features()->create(['label' => $label, 'value' => $value, 'order' => $i]);
            }
        }
        $group->options()->create(['name' => 'Nom de domaine', 'description' => 'Enregistrement et renouvellement (exemple)', 'price_label' => 'Sur devis']);
    }

    private function articles(array $data): void
    {
        $author = User::where('role', Role::Superadmin)->first();
        $tag = BlogTag::firstOrCreate(['slug' => 'exemple'], ['name' => 'Exemple']);
        foreach ($data['articles'] as $i => $item) {
            $article = BlogArticle::firstOrCreate(['slug' => $item['slug']], [
                'title' => $item['title'], 'excerpt' => $item['excerpt'], 'body' => $data['body'], 'blog_category_id' => BlogCategory::where('slug', $item['category'])->value('id'),
                'author_id' => $author?->id, 'status' => 'published', 'published_at' => now()->subDays(4 * ($i + 1)),
            ]);
            $article->tags()->syncWithoutDetaching([$tag->id]);
        }
        BlogArticle::firstOrCreate(['slug' => 'article-programme-exemple'], [
            'title' => 'Article programmé (exemple)', 'excerpt' => 'Illustration de la publication différée.', 'body' => $data['body'],
            'status' => 'published', 'published_at' => now()->addDays(7), 'author_id' => $author?->id,
        ]);
    }

    private function caseStudy(int $custom): void
    {
        $case = CaseStudy::firstOrCreate(['slug' => 'digitalisation-cooperative-exemple'], [
            'title' => "Digitalisation d'une coopérative agricole", 'sector' => 'Agriculture', 'is_example' => true, 'status' => 'published',
            'summary' => 'Exemple illustratif : suivi des collectes et paiements des producteurs via une application mobile hors-ligne.',
            'challenge' => '<p>Exemple : les collectes étaient enregistrées sur papier, ce qui retardait les paiements et compliquait les contrôles.</p>',
            'solution' => '<p>Exemple : application mobile utilisable sans connexion, synchronisée dès que le réseau est disponible, et tableau de bord web.</p>',
            'results' => '<p>Section à remplir uniquement avec des résultats mesurés et validés par le client.</p>',
            'technologies' => 'Laravel, PostgreSQL, Vue.js, Flutter, Docker',
        ]);
        $case->services()->syncWithoutDetaching([$custom]);
    }

    private function crm($categories): void
    {
        if (Lead::where('email', 'like', '%@example.com')->exists()) {
            return;
        }
        $samples = [
            ['Aïssatou', 'Exemple', 'Entreprise Exemple SARL', LeadStatus::New, InquiryKind::Quote, 'Migration de notre site vers un hébergement administré', 'cloud-computing'],
            ['Mamadou', 'Démo', 'Association Démo', LeadStatus::Qualified, InquiryKind::Demo, "Démonstration de l'outil RH", 'saas'],
            ['Fatoumata', 'Test', 'Institution Test', LeadStatus::Proposal, InquiryKind::Contact, "Projet d'assistant documentaire", 'intelligence-artificielle'],
            ['Ibrahima', 'Fictif', 'Commerce Fictif', LeadStatus::Won, InquiryKind::Quote, 'Application de gestion des ventes', 'developpement-sur-mesure'],
        ];
        foreach ($samples as $i => [$first, $last, $org, $status, $kind, $subject, $category]) {
            $email = Str::slug($first).'.'.Str::slug($last).'@example.com';
            $lead = Lead::create([
                'first_name' => $first, 'last_name' => $last, 'email' => $email, 'organization' => $org, 'status' => $status,
                'service_category_id' => $categories[$category], 'priority' => $i === 0 ? 'high' : 'normal', 'notes' => '[Démo] Donnée de démonstration.',
                'next_follow_up_at' => $status === LeadStatus::Won ? null : now()->addDays($i - 1), 'follow_up_note' => $i < 3 ? 'Rappeler pour qualifier le besoin' : '',
                'is_client' => $status === LeadStatus::Won, 'converted_at' => $status === LeadStatus::Won ? now()->subDays(2) : null,
            ]);
            $inquiry = Inquiry::create([
                'kind' => $kind, 'first_name' => $first, 'last_name' => $last, 'organization' => $org, 'email' => $email, 'subject' => $subject,
                'message' => '[Démo] Message de démonstration généré par le seeder.', 'service_category_id' => $categories[$category],
                'consent' => true, 'consent_at' => now(), 'lead_id' => $lead->id, 'status' => $i === 0 ? InquiryStatus::New : InquiryStatus::InProgress,
            ]);
            Activity::create(['lead_id' => $lead->id, 'inquiry_id' => $inquiry->id, 'kind' => 'inquiry', 'content' => "Demande reçue : « {$subject} » ({$inquiry->reference}).", 'created_at' => now()->subDays($i)]);
        }
    }
}
