<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/** Single row holding the company identity, editable from the back-office. */
class SiteSetting extends Model
{
    public const CACHE_KEY = 'site-settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['notify_on_inquiry' => 'boolean', 'send_acknowledgement' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function current(): self
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $site = self::query()->firstOrCreate(['id' => 1], [
                'hosting_disclosure' => "Nos services d'hébergement s'appuient aujourd'hui sur des infrastructures louées auprès de fournisseurs cloud établis, situées hors de Guinée. Le déploiement d'une infrastructure locale fait partie de notre feuille de route.",
            ]);

            // A freshly inserted row lacks the column defaults set by the database (company_name…).
            return $site->wasRecentlyCreated ? $site->refresh() : $site;
        });
    }

    /** @return array<int, string> */
    public function notificationRecipients(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->notification_emails))));
    }

    /** @return array<int, array{label: string, url: string, icon: string}> */
    public function socialLinks(): array
    {
        $links = [
            ['label' => 'LinkedIn', 'url' => $this->linkedin_url, 'icon' => 'brand-linkedin'],
            ['label' => 'X', 'url' => $this->x_url, 'icon' => 'brand-x'],
            ['label' => 'Facebook', 'url' => $this->facebook_url, 'icon' => 'brand-facebook'],
            ['label' => 'Instagram', 'url' => $this->instagram_url, 'icon' => 'brand-instagram'],
            ['label' => 'YouTube', 'url' => $this->youtube_url, 'icon' => 'brand-youtube'],
            ['label' => 'GitHub', 'url' => $this->github_url, 'icon' => 'brand-github'],
        ];

        return array_values(array_filter($links, fn ($link) => $link['url']));
    }

    /** Public, non-sensitive subset shared with every page. */
    public function toPublicArray(): array
    {
        $url = fn (?string $path) => $path ? Storage::disk('media')->url($path) : null;

        return [
            'company_name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'tagline' => $this->tagline,
            'logo' => $url($this->logo),
            'logo_dark' => $url($this->logo_dark),
            'registration_number' => $this->registration_number,
            'colors' => ['primary' => $this->color_primary, 'accent' => $this->color_accent, 'ink' => $this->color_ink],
            'email_contact' => $this->email_contact,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'opening_hours' => $this->opening_hours,
            'map_embed_url' => $this->map_embed_url,
            'social' => $this->socialLinks(),
            'hosting_disclosure' => $this->hosting_disclosure,
            'footer_text' => $this->footer_text,
            'announcement' => $this->announcement,
            'announcement_url' => $this->announcement_url,
            'send_acknowledgement' => $this->send_acknowledgement,
            'year' => now()->year,
        ];
    }
}
