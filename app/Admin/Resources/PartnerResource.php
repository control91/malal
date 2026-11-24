<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Enums\PartnerKind;
use App\Models\Partner;

class PartnerResource extends Resource
{
    public static string $slug = 'partenaires';

    public static string $model = Partner::class;

    public static string $area = 'content';

    public static string $label = 'partenaire';

    public static string $labelPlural = 'Partenaires';

    public static string $icon = 'handshake';

    public static string $description = 'Ne publiez que des partenariats formalisés autorisant la mention publique.';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['name'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('name', 'Nom', 'title'), new C('kind', 'Type'), new C('agreement_confirmed', 'Formalisé', 'bool'), new C('is_published', 'Publié', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Partenaire', 'fields' => [
            F::text('name', 'Nom')->required()->max(120), F::enum('kind', 'Type', PartnerKind::class)->required(), F::logo('logo', 'Logo'), F::url('url', 'Site web'),
            F::textarea('description', 'Description'), F::boolean('agreement_confirmed', 'Partenariat formalisé'), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'),
        ]]];
    }
}
