<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Enums\ContractType;
use App\Models\JobOpening;

class JobResource extends Resource
{
    public static string $slug = 'offres-emploi';

    public static string $model = JobOpening::class;

    public static string $area = 'content';

    public static string $label = "offre d'emploi";

    public static string $labelPlural = 'Carrières';

    public static string $icon = 'briefcase';

    public static ?string $publishField = 'is_open';

    public static array $searchable = ['title'];

    public static function columns(): array
    {
        return [new C('title', 'Intitulé', 'title'), new C('contract', 'Contrat'), new C('location', 'Lieu'), new C('closes_on', 'Date limite', 'date'), new C('is_open', 'Ouverte', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Offre', 'fields' => [
            F::text('title', 'Intitulé')->required()->max(160), F::slug(), F::text('department', 'Pôle')->max(80), F::text('location', 'Lieu')->required()->max(80),
            F::enum('contract', 'Contrat', ContractType::class)->required(), F::boolean('remote_possible', 'Télétravail possible'), F::text('summary', 'Résumé')->max(255)->wide(),
            F::richtext('description', 'Description')->required(), F::date('closes_on', 'Date limite'), F::boolean('is_open', 'Ouverte'),
        ]]];
    }
}
