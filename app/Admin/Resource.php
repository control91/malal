<?php

namespace App\Admin;

use App\Enums\PublishStatus;
use App\Models\User;
use App\Support\Images;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Declarative description of how a model is listed, filtered, edited and audited
 * in the back-office. Adding a model to the back-office = adding a Resource class
 * and registering it in Registry. Every action re-checks "<area>.<view|edit>".
 */
abstract class Resource
{
    public static string $slug;

    /** @var class-string<Model> */
    public static string $model;

    public static string $area;

    public static string $label;

    public static string $labelPlural;

    public static string $icon = 'file';

    public static string $group = 'Site';

    public static string $description = '';

    public static bool $canCreate = true;

    public static bool $canEdit = true;

    public static bool $canDelete = true;

    public static bool $showInMenu = true;

    /** "status" (PublishStatus) or the name of a boolean column. */
    public static ?string $publishField = null;

    /** @var array<int, string> */
    public static array $searchable = [];

    /** @var array<int, string> */
    public static array $with = [];

    public static array $defaultSort = ['id', 'desc'];

    public static int $perPage = 25;

    /** Route name of a dedicated detail page (inquiries, leads). */
    public static ?string $detailRoute = null;

    /** Vue page used for the form (defaults to the generic one). */
    public static string $formPage = 'Admin/Resource/Form';

    public static string $indexPage = 'Admin/Resource/Index';

    /** @var array<int, string> */
    public static array $exportable = [];

    /** @return array<int, Column> */
    abstract public static function columns(): array;

    /** @return array<int, array{title: string, fields: array<int, Field>}> */
    public static function fieldsets(): array
    {
        return [];
    }

    /** @return array<int, Repeater> */
    public static function repeaters(): array
    {
        return [];
    }

    /** @return array<int, array{name: string, label: string, options: array}> */
    public static function filters(): array
    {
        return [];
    }

    public static function query(): Builder
    {
        return static::$model::query()->with(static::$with);
    }

    /** Values displayed in the list for one record. */
    public static function row(Model $record): array
    {
        $row = ['id' => $record->getKey()];
        foreach (static::columns() as $column) {
            $value = data_get($record, $column->key);
            if ($value instanceof \BackedEnum) {
                $value = method_exists($value, 'label') ? $value->label() : $value->value;
            } elseif ($value instanceof Model) {
                $value = (string) $value;
            } elseif ($column->type === 'image') {
                $value = Images::url($value);
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format(DATE_ATOM);
            }
            $row[$column->key] = $value;
        }

        return $row;
    }

    /** @return array<int, Field> */
    public static function fields(): array
    {
        return array_merge(...array_map(fn ($set) => $set['fields'], static::fieldsets() ?: [['fields' => []]]));
    }

    public static function rules(?Model $record): array
    {
        $rules = [];
        foreach (static::fields() as $field) {
            $rules[$field->name] = $field->validationRules($record);
            if ($field->type === 'slug') {
                $rules[$field->name][] = Rule::unique((new static::$model)->getTable(), $field->name)->ignore($record?->getKey());
            }
        }
        foreach (static::repeaters() as $repeater) {
            $rules += $repeater->rules();
        }

        return $rules;
    }

    /** Current values of the form fields for an existing record. */
    public static function values(Model $record): array
    {
        $values = [];
        foreach (static::fields() as $field) {
            $value = $record->getAttribute($field->name);
            $values[$field->name] = match (true) {
                $field->type === 'multiselect' => $record->{$field->name}()->pluck('id')->all(),
                $field->isUpload() => ['path' => $value, 'url' => Images::url($value)],
                $field->type === 'password' => '',
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $field->type === 'date' ? $value->format('Y-m-d') : $value->format('Y-m-d\TH:i'),
                default => $value,
            };
        }
        foreach (static::repeaters() as $repeater) {
            $values[$repeater->relation] = $record->{$repeater->relation}->map(function ($item) use ($repeater) {
                $row = ['id' => $item->id];
                foreach ($repeater->fields as $field) {
                    $value = $item->getAttribute($field->name);
                    $row[$field->name] = $field->isUpload() ? ['path' => $value, 'url' => Images::url($value)] : ($value instanceof \BackedEnum ? $value->value : $value);
                }

                return $row;
            })->all();
        }

        return $values;
    }

    /** Defaults for a new record (query string pre-fill, e.g. ?page_id=3). */
    public static function defaults(Request $request): array
    {
        $values = [];
        foreach (static::fields() as $field) {
            $value = $request->query($field->name, match ($field->type) {
                'boolean' => false, 'multiselect' => [], 'number' => $field->fallback ?? '', default => $field->isUpload() ? null : '',
            });
            $values[$field->name] = match (true) {
                $field->type === 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                $field->type === 'select' && is_numeric($value) && $field->optionsModel !== null => (int) $value,
                default => $value,
            };
        }
        foreach (static::repeaters() as $repeater) {
            $values[$repeater->relation] = [];
        }

        return $values;
    }

    public static function beforeSave(Model $record, array &$data, Request $request, bool $creating): void {}

    public static function afterSave(Model $record, Request $request, bool $creating): void {}

    public static function canDeleteRecord(Model $record): bool
    {
        return static::$canDelete && ! ($record->is_system ?? false);
    }

    public static function isPublished(Model $record): ?bool
    {
        if (! static::$publishField) {
            return null;
        }
        $value = $record->getAttribute(static::$publishField);

        return static::$publishField === 'status' ? $value === PublishStatus::Published : (bool) $value;
    }

    public static function publicUrl(Model $record): ?string
    {
        return method_exists($record, 'url') && static::isPublished($record) !== false ? $record->url() : null;
    }

    public static function viewCapability(): string
    {
        return static::$area.'.view';
    }

    public static function editCapability(): string
    {
        return static::$area.'.edit';
    }

    public static function allows(?User $user, string $action = 'view'): bool
    {
        return (bool) $user?->canDo(static::$area.'.'.$action);
    }

    public static function meta(): array
    {
        return [
            'slug' => static::$slug, 'label' => static::$label, 'labelPlural' => static::$labelPlural, 'icon' => static::$icon,
            'description' => static::$description, 'canCreate' => static::$canCreate, 'canEdit' => static::$canEdit,
            'canDelete' => static::$canDelete, 'publishable' => (bool) static::$publishField, 'exportable' => (bool) static::$exportable,
            'searchable' => (bool) static::$searchable, 'detailRoute' => static::$detailRoute,
        ];
    }
}
