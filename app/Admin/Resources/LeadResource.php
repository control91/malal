<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Enums\Currency;
use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LeadResource extends Resource
{
    public static string $slug = 'prospects';

    public static string $model = Lead::class;

    public static string $area = 'crm';

    public static string $label = 'prospect';

    public static string $labelPlural = 'Prospects';

    public static string $icon = 'contact';

    public static string $group = 'Commercial';

    public static ?string $detailRoute = 'admin.leads.show';

    public static array $with = ['owner', 'interest'];

    public static array $searchable = ['first_name', 'last_name', 'email', 'organization'];

    public static array $defaultSort = ['created_at', 'desc'];

    public static array $exportable = ['created_at', 'first_name', 'last_name', 'email', 'phone', 'organization', 'job_title', 'country', 'source', 'status', 'priority', 'owner', 'interest', 'estimated_value', 'currency', 'next_follow_up_at', 'is_client', 'converted_at'];

    public static function columns(): array
    {
        return [new C('full_name', 'Prospect', 'title'), new C('organization', 'Entreprise'), new C('status', 'Statut', 'badge'), new C('priority', 'Priorité'),
            new C('owner', 'Responsable'), new C('next_follow_up_at', 'Relance', 'datetime', 'next_follow_up_at'), new C('created_at', 'Créé', 'datetime', 'created_at')];
    }

    public static function filters(): array
    {
        return [Filters::enum('status', 'Statut', LeadStatus::class), Filters::enum('priority', 'Priorité', LeadPriority::class), Filters::model('owner_id', 'Responsable', User::class, 'first_name'),
            Filters::enum('source', 'Source', LeadSource::class), Filters::boolean('is_client', 'Client')];
    }

    public static function fieldsets(): array
    {
        return [
            ['title' => 'Contact', 'fields' => [
                F::text('first_name', 'Prénom')->required()->max(80), F::text('last_name', 'Nom')->max(80), F::email('email', 'Email')->required()->max(254), F::text('phone', 'Téléphone')->max(40),
                F::text('organization', 'Entreprise')->max(160), F::text('job_title', 'Fonction')->max(120), F::text('country', 'Pays')->max(80),
            ]],
            ['title' => 'Qualification', 'fields' => [
                F::enum('source', 'Source', LeadSource::class)->required(), F::enum('status', 'Statut', LeadStatus::class)->required(), F::enum('priority', 'Priorité', LeadPriority::class)->required(),
                F::belongsTo('owner_id', 'Responsable', User::class, 'first_name'), F::belongsTo('service_category_id', 'Service demandé', ServiceCategory::class), F::belongsTo('product_id', 'Solution', Product::class),
                F::number('estimated_value', 'Valeur estimée'), F::enum('currency', 'Devise', Currency::class)->required(),
            ]],
            ['title' => 'Suivi', 'fields' => [F::datetime('next_follow_up_at', 'Prochaine relance'), F::text('follow_up_note', 'Objet de la relance')->max(200), F::text('lost_reason', 'Raison de la perte')->max(200), F::textarea('notes', 'Notes internes')]],
        ];
    }

    public static function defaults(Request $request): array
    {
        return ['source' => 'other', 'status' => 'new', 'priority' => 'normal', 'currency' => 'GNF'] + parent::defaults($request);
    }

    public static function redirectTo(Model $record, Request $request): ?string
    {
        return route('admin.leads.show', $record);
    }
}
