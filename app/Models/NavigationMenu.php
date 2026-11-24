<?php

namespace App\Models;

use App\Enums\MenuLocation;
use App\Enums\NavStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class NavigationMenu extends Model
{
    public const CACHE_KEY = 'navigation-menus';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['key' => MenuLocation::class];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('order')->orderBy('id');
    }

    /** Menus as plain arrays, keyed by location, cached for 10 minutes. */
    public static function tree(): array
    {
        return Cache::remember(self::CACHE_KEY, 600, function () {
            $menus = [];
            $all = self::with(['items' => fn ($q) => $q->where('is_active', true)])->get();
            foreach ($all as $menu) {
                $items = $menu->items;
                $map = fn (NavigationItem $item) => [
                    'label' => $item->label,
                    'url' => $item->url,
                    'description' => $item->description,
                    'icon' => $item->icon,
                    'new_tab' => $item->open_in_new_tab,
                    'is_button' => $item->style === NavStyle::Button->value,
                ];
                $menus[$menu->key->value] = [
                    'title' => $menu->title,
                    'items' => $items->whereNull('parent_id')->values()->map(fn ($item) => $map($item) + [
                        'children' => $items->where('parent_id', $item->id)->values()->map($map)->all(),
                    ])->all(),
                ];
            }

            return $menus;
        });
    }

    public function __toString(): string
    {
        return $this->key->label();
    }
}
