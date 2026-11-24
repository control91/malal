<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Enums\MenuLocation;
use App\Enums\NavStyle;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;

class MenuResource extends Resource
{
    public static string $slug = 'menus';

    public static string $model = NavigationMenu::class;

    public static string $area = 'content';

    public static string $label = 'menu';

    public static string $labelPlural = 'Navigation';

    public static string $icon = 'navigation';

    public static string $description = 'En-tête et colonnes du footer. Pour créer un sous-menu, choisissez un élément parent.';

    public static bool $canDelete = false;

    public static array $defaultSort = ['id', 'asc'];

    public static function columns(): array
    {
        return [new C('key', 'Emplacement', 'title'), new C('title', 'Titre affiché')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Menu', 'fields' => [F::enum('key', 'Emplacement', MenuLocation::class)->required(), F::text('title', 'Titre affiché')->max(80)]]];
    }

    public static function repeaters(): array
    {
        return [new Repeater('items', 'Liens du menu', [
            F::integer('order', 'Ordre'), F::text('label', 'Libellé')->required()->max(80), F::text('url', 'Lien')->required()->max(255),
            F::belongsTo('parent_id', 'Élément parent (sous-menu)', NavigationItem::class, 'label'),
            F::text('description', 'Description courte')->max(140), F::icon(), F::enum('style', 'Style', NavStyle::class)->required(),
            F::boolean('open_in_new_tab', 'Nouvel onglet'), F::boolean('is_active', 'Actif'),
        ])];
    }
}
