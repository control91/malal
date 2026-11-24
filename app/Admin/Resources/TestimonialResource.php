<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Models\CaseStudy;
use App\Models\Testimonial;

class TestimonialResource extends Resource
{
    public static string $slug = 'temoignages';

    public static string $model = Testimonial::class;

    public static string $area = 'portfolio';

    public static string $label = 'témoignage';

    public static string $labelPlural = 'Témoignages';

    public static string $icon = 'quote';

    public static string $group = 'Éditorial';

    public static string $description = 'Uniquement des témoignages réels, avec consentement écrit.';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['author_name', 'organization'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('author_name', 'Auteur', 'title'), new C('organization', 'Organisation'), new C('consent_obtained', 'Consentement', 'bool'), new C('is_published', 'Publié', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Témoignage', 'fields' => [
            F::text('author_name', 'Nom')->required()->max(120), F::text('author_role', 'Fonction')->max(120), F::text('organization', 'Organisation')->max(160),
            F::textarea('quote', 'Témoignage')->required(), F::image('photo', 'Photo'), F::belongsTo('case_study_id', 'Réalisation', CaseStudy::class, 'title'),
            F::boolean('consent_obtained', 'Consentement écrit obtenu'), F::text('consent_reference', 'Référence du consentement')->max(200), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'),
        ]]];
    }
}
