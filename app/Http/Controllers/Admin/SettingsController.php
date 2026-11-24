<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Field as F;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\Audit;
use App\Support\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public static function groups(): array
    {
        return [
            ['title' => 'Identité', 'icon' => 'building-2', 'fields' => [
                F::text('company_name', 'Nom commercial')->required()->max(120), F::text('legal_name', 'Raison sociale')->max(200), F::text('tagline', 'Signature')->required()->max(200)->wide(),
                F::logo('logo', 'Logo (fond clair)'), F::logo('logo_dark', 'Logo (fond sombre)'), F::logo('favicon', 'Favicon'),
                F::text('registration_number', 'RCCM')->max(80), F::text('tax_number', 'NIF')->max(80),
            ]],
            ['title' => 'Couleurs de marque', 'icon' => 'palette', 'fields' => [F::color('color_primary', 'Couleur principale')->required(), F::color('color_accent', "Couleur d'accent")->required(), F::color('color_ink', 'Bleu nuit')->required()]],
            ['title' => 'Coordonnées', 'icon' => 'map-pin', 'fields' => [
                F::email('email_contact', 'Email de contact')->required(), F::email('email_sales', 'Email commercial'), F::email('email_support', 'Email support'),
                F::text('phone', 'Téléphone')->max(40), F::text('whatsapp', 'WhatsApp')->max(40), F::text('address', 'Adresse')->max(255), F::text('city', 'Ville')->max(80),
                F::text('country', 'Pays')->max(80), F::text('opening_hours', 'Horaires')->max(120), F::url('map_embed_url', 'URL de carte intégrée (OpenStreetMap)')->max(500),
            ]],
            ['title' => 'Réseaux sociaux', 'icon' => 'share-2', 'fields' => [F::url('linkedin_url', 'LinkedIn'), F::url('x_url', 'X (Twitter)'), F::url('facebook_url', 'Facebook'), F::url('instagram_url', 'Instagram'), F::url('youtube_url', 'YouTube'), F::url('github_url', 'GitHub')]],
            ['title' => 'SEO par défaut', 'icon' => 'search', 'fields' => [F::text('meta_title', 'Meta title')->max(70)->counter(60), F::text('meta_description', 'Meta description')->max(170)->counter(155)->wide(), F::image('og_image', 'Image de partage'), F::text('twitter_handle', 'Compte X (@…)')->max(40)]],
            ['title' => 'Contenus transverses', 'icon' => 'type', 'fields' => [
                F::textarea('hosting_disclosure', "Transparence sur l'hébergement")->help('Affiché sur les pages Cloud et Tarifs. Restez factuel sur la localisation des données.'),
                F::textarea('footer_text', 'Texte du footer'), F::text('announcement', "Bandeau d'annonce")->max(200), F::text('announcement_url', 'Lien du bandeau')->max(255),
            ]],
            ['title' => 'Langues et notifications', 'icon' => 'bell', 'fields' => [
                F::text('enabled_languages', 'Langues actives')->required()->max(60)->help('Codes séparés par des virgules (fr,en).')->rules(['regex:/^(fr|en)(,(fr|en))*$/']),
                F::textarea('notification_emails', 'Emails de notification')->help('Une adresse par ligne : reçoivent les nouvelles demandes.'),
                F::boolean('notify_on_inquiry', 'Notifier les nouvelles demandes par email'), F::boolean('send_acknowledgement', 'Envoyer un accusé de réception aux demandeurs'),
            ]],
        ];
    }

    public function edit(Request $request): Response
    {
        $settings = SiteSetting::current()->fresh();
        $values = [];
        foreach (self::fields() as $field) {
            $value = $settings->{$field->name};
            $values[$field->name] = $field->isUpload() ? ['path' => $value, 'url' => Images::url($value)] : $value;
        }

        return Inertia::render('Admin/Settings', [
            'groups' => array_map(fn ($g) => ['title' => $g['title'], 'icon' => $g['icon'], 'fields' => array_map(fn ($f) => $f->schema(), $g['fields'])], self::groups()),
            'values' => $values,
            'canEdit' => $request->user()->canDo('settings.edit'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::fields() as $field) {
            $rules[$field->name] = $field->validationRules();
        }
        $validated = $request->validate($rules);
        $emails = array_filter(array_map('trim', preg_split('/\R/', (string) ($validated['notification_emails'] ?? ''))));
        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['notification_emails' => "Adresse invalide : $email"]);
            }
        }
        if (! str_contains($validated['enabled_languages'], 'fr')) {
            return back()->withErrors(['enabled_languages' => 'Le français doit rester actif.']);
        }

        $settings = SiteSetting::current()->fresh();
        foreach (self::fields() as $field) {
            if ($field->isUpload()) {
                if ($request->hasFile($field->name)) {
                    $settings->{$field->name} = Images::store($request->file($field->name), 'brand');
                } elseif ($request->boolean("{$field->name}_clear")) {
                    $settings->{$field->name} = null;
                }
            } elseif ($field->type === 'boolean') {
                $settings->{$field->name} = $request->boolean($field->name);
            } else {
                $settings->{$field->name} = $validated[$field->name] ?? '';
            }
        }
        $settings->notification_emails = implode("\n", $emails);
        $settings->save();
        Audit::record(AuditAction::Settings, $settings, Audit::diff($settings));

        return back()->with('success', 'Paramètres enregistrés.');
    }

    private static function fields(): array
    {
        return array_merge(...array_map(fn ($g) => $g['fields'], self::groups()));
    }
}
