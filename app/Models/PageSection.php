<?php

namespace App\Models;

use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A configurable block of a page; `kind` selects the Vue component that renders it. */
class PageSection extends Model
{
    use SanitizesRichText;

    public const KINDS = [
        'hero' => 'Hero (bandeau principal)',
        'marquee' => 'Bandeau défilant (domaines)',
        'domains' => "Domaines d'expertise (catégories)",
        'solutions' => 'Solutions mises en avant',
        'features' => "Grille d'éléments (icône + texte)",
        'vision' => 'Vision Afrique (carte)',
        'news' => 'Dernières actualités',
        'text' => 'Texte riche',
        'split' => 'Texte + image',
        'roadmap' => 'Feuille de route',
        'team' => 'Équipe',
        'partners' => 'Partenaires',
        'testimonials' => 'Témoignages',
        'case_studies' => 'Réalisations',
        'faq' => 'Questions fréquentes',
        'cta' => "Appel à l'action",
    ];

    protected $guarded = ['id'];

    protected array $richText = ['body'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'limit' => 'integer', 'order' => 'integer'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SectionItem::class)->orderBy('order')->orderBy('id');
    }

    public function __toString(): string
    {
        return $this->title ?: (self::KINDS[$this->kind] ?? $this->kind);
    }
}
