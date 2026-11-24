<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Resource;
use App\Models\NewsletterSubscriber;

class NewsletterResource extends Resource
{
    public static string $slug = 'newsletter';

    public static string $model = NewsletterSubscriber::class;

    public static string $area = 'newsletter';

    public static string $label = 'abonné';

    public static string $labelPlural = 'Newsletter';

    public static string $icon = 'mail';

    public static string $group = 'Commercial';

    public static bool $canCreate = false;

    public static bool $canEdit = false;

    public static array $searchable = ['email'];

    public static array $defaultSort = ['created_at', 'desc'];

    public static array $exportable = ['email', 'consent_at', 'confirmed_at', 'unsubscribed_at', 'source'];

    public static function columns(): array
    {
        return [new C('email', 'Email', 'title'), new C('is_active', 'Actif', 'bool'), new C('confirmed_at', 'Confirmé', 'datetime'), new C('unsubscribed_at', 'Désinscrit', 'datetime'), new C('created_at', 'Inscription', 'datetime', 'created_at')];
    }
}
