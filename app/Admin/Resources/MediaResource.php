<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaResource extends Resource
{
    public static string $slug = 'medias';

    public static string $model = MediaAsset::class;

    public static string $area = 'media';

    public static string $label = 'média';

    public static string $labelPlural = 'Médiathèque';

    public static string $icon = 'images';

    public static string $description = 'Images et documents réutilisables : copiez leur adresse pour les insérer dans un contenu.';

    public static array $searchable = ['title', 'alt_text'];

    public static int $perPage = 24;

    public static string $indexPage = 'Admin/Media/Index';

    public static function columns(): array
    {
        return [new C('file', 'Aperçu', 'image'), new C('title', 'Titre', 'title'), new C('extension', 'Type', 'muted'), new C('size', 'Taille'), new C('created_at', 'Ajouté', 'datetime', 'created_at')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Média', 'fields' => [F::text('title', 'Titre')->required()->max(160), F::file('file', 'Fichier')->help('Images, PDF, documents Office, archives ZIP.'), F::text('alt_text', 'Texte alternatif')->max(200)]]];
    }

    public static function rules(?Model $record): array
    {
        $rules = parent::rules($record);
        $rules['file'][0] = $record ? 'nullable' : 'required';

        return $rules;
    }

    public static function beforeSave(Model $record, array &$data, Request $request, bool $creating): void
    {
        if (! empty($data['file'])) {
            $data['size'] = (int) Storage::disk('media')->size($data['file']);
        }
        if ($creating) {
            $data['uploaded_by'] = $request->user()->id;
        }
    }
}
