<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Admin\Registry;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Enums\AuditAction;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\Testimonial;
use App\Support\Audit;
use App\Support\Images;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generic CRUD driven by Resource classes. Every action checks the resource's
 * capability server-side: "<area>.view" to read, "<area>.edit" to write.
 */
class ResourceController extends Controller
{
    public function index(Request $request, string $resource): Response
    {
        $res = $this->resource($resource, 'view', $request);
        $query = $this->filtered($res, $request);
        $records = $query->paginate($res::$perPage)->withQueryString();
        $canEdit = $res::allows($request->user(), 'edit');

        return Inertia::render($res::$indexPage, [
            'resource' => $res::meta(),
            'columns' => array_map(fn ($c) => $c->schema(), $res::columns()),
            'filters' => array_map(fn ($f) => $f + ['value' => (string) $request->query($f['name'], '')], $res::filters()),
            'query' => ['q' => (string) $request->query('q', ''), 'sort' => (string) $request->query('sort', '')],
            'canEdit' => $canEdit,
            'records' => $records->through(fn (Model $record) => $res::row($record) + [
                '_published' => $res::isPublished($record),
                '_canDelete' => $canEdit && $res::canDeleteRecord($record),
                '_url' => $res::$detailRoute ? route($res::$detailRoute, $record) : ($res::$canEdit ? route('admin.resources.edit', [$res::$slug, $record->getKey()]) : null),
                '_label' => (string) $record,
            ]),
        ]);
    }

    public function create(Request $request, string $resource): Response
    {
        $res = $this->resource($resource, 'edit', $request);
        abort_unless($res::$canCreate, 404);

        return $this->form($res, null, $res::defaults($request), true);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $res = $this->resource($resource, 'edit', $request);
        abort_unless($res::$canCreate, 404);
        $record = new $res::$model;
        $this->save($res, $record, $request, true);

        return $this->redirectAfterSave($res, $record, $request, true);
    }

    public function edit(Request $request, string $resource, int $id): Response
    {
        $res = $this->resource($resource, 'view', $request);
        abort_unless($res::$canEdit, 404);
        $record = $res::query()->findOrFail($id);

        return $this->form($res, $record, $res::values($record), $res::allows($request->user(), 'edit'));
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $res = $this->resource($resource, 'edit', $request);
        abort_unless($res::$canEdit, 404);
        $record = $res::query()->findOrFail($id);
        $this->save($res, $record, $request, false);

        return $this->redirectAfterSave($res, $record, $request, false);
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $res = $this->resource($resource, 'edit', $request);
        $record = $res::query()->findOrFail($id);
        if (! $res::canDeleteRecord($record)) {
            return back()->with('error', 'Cet élément ne peut pas être supprimé.');
        }
        $label = (string) $record;
        try {
            DB::transaction(function () use ($record, $label) {
                Audit::record(AuditAction::Delete, $record, repr: $label);
                $record->delete();
            });
        } catch (QueryException) {
            return back()->with('error', "« {$label} » est utilisé ailleurs et ne peut pas être supprimé.");
        }

        $redirect = $request->input('redirect');

        return (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')
            ? redirect($redirect) : redirect()->route('admin.resources.index', $res::$slug))->with('success', "« {$label} » a été supprimé.");
    }

    public function toggle(Request $request, string $resource, int $id): RedirectResponse
    {
        $res = $this->resource($resource, 'edit', $request);
        abort_unless($res::$publishField, 404);
        $record = $res::query()->findOrFail($id);
        $publish = ! $res::isPublished($record);
        if ($res::$publishField === 'status') {
            $record->status = $publish ? PublishStatus::Published : PublishStatus::Draft;
        } else {
            $record->{$res::$publishField} = $publish;
        }
        if ($error = $this->publicationError($record)) {
            return back()->with('error', "Publication impossible : $error");
        }
        $record->save();
        Audit::record($publish ? AuditAction::Publish : AuditAction::Unpublish, $record);

        return back()->with('success', '« '.$record.' » '.($publish ? 'publié' : 'dépublié').'.');
    }

    public function export(Request $request, string $resource): StreamedResponse
    {
        $res = $this->resource($resource, 'view', $request);
        abort_unless($res::$exportable, 404);
        $query = $this->filtered($res, $request);
        $count = (clone $query)->count();
        Audit::record(AuditAction::Export, repr: "Export CSV {$res::$labelPlural} ({$count} lignes)");

        return response()->streamDownload(function () use ($res, $query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel-friendly UTF-8
            fputcsv($out, $res::$exportable, ';');
            $query->chunk(500, function ($records) use ($out, $res) {
                foreach ($records as $record) {
                    fputcsv($out, array_map(function ($key) use ($record) {
                        $value = data_get($record, $key);
                        $value = match (true) {
                            $value instanceof \BackedEnum => method_exists($value, 'label') ? $value->label() : $value->value,
                            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i'),
                            $value instanceof Model => (string) $value,
                            is_bool($value) => $value ? 'oui' : 'non',
                            default => (string) $value,
                        };

                        return in_array(substr($value, 0, 1), ['=', '+', '-', '@'], true) ? "'".$value : $value; // CSV formula injection guard
                    }, $res::$exportable), ';');
                }
            });
            fclose($out);
        }, $res::$slug.'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /* ------------------------------------------------------------------ */

    /** @return class-string<resource> */
    private function resource(string $slug, string $action, Request $request): string
    {
        $res = Registry::find($slug);
        abort_unless($res, 404);
        abort_unless($res::allows($request->user(), $action), 403);

        return $res;
    }

    private function filtered(string $res, Request $request)
    {
        $query = $res::query();
        $q = mb_substr(trim((string) $request->query('q')), 0, 100);
        if ($q !== '' && $res::$searchable) {
            $query->where(function ($w) use ($res, $q) {
                foreach ($res::$searchable as $column) {
                    $w->orWhereLike($column, "%$q%");
                }
            });
        }
        foreach ($res::filters() as $filter) {
            $value = $request->query($filter['name']);
            if ($value !== null && $value !== '' && in_array((string) $value, array_map('strval', array_column($filter['options'], 'value')), true)) {
                $query->where($filter['name'], in_array($value, ['0', '1'], true) && ($filter['boolean'] ?? false) ? (bool) $value : $value);
            }
        }
        $sort = (string) $request->query('sort');
        $sortable = array_filter(array_map(fn ($c) => $c->sort, $res::columns()));
        if ($sort !== '' && in_array(ltrim($sort, '-'), $sortable, true)) {
            $query->reorder()->orderBy(ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc');
        } elseif (! $query->getQuery()->orders) {
            $query->orderBy(...$res::$defaultSort);
        }

        return $query;
    }

    private function form(string $res, ?Model $record, array $values, bool $canEdit): Response
    {
        $fieldsets = array_map(fn ($set) => ['title' => $set['title'], 'fields' => array_map(fn (Field $f) => $f->schema(), $set['fields'])], $res::fieldsets());

        return Inertia::render($res::$formPage, [
            'resource' => $res::meta(),
            'record' => $record ? [
                'id' => $record->getKey(), 'label' => (string) $record, 'published' => $res::isPublished($record),
                'updated_at' => $record->updated_at?->toIso8601String(), 'public_url' => $res::publicUrl($record),
                'can_delete' => $canEdit && $res::canDeleteRecord($record),
            ] : null,
            'fieldsets' => $fieldsets,
            'repeaters' => array_map(fn ($r) => $r->schema(), $res::repeaters()),
            'values' => $values,
            'canEdit' => $canEdit,
            'extra' => method_exists($res, 'extra') ? $res::extra($record) : null,
        ]);
    }

    private function save(string $res, Model $record, Request $request, bool $creating): void
    {
        $validated = $request->validate($res::rules($creating ? null : $record), [], $this->attributeNames($res));
        $wasPublished = $creating ? false : $res::isPublished($record);
        $data = [];
        $many = [];

        foreach ($res::fields() as $field) {
            $name = $field->name;
            if ($field->isUpload()) {
                if ($request->hasFile($name)) {
                    $data[$name] = Images::store($request->file($name), $res::$slug);
                } elseif ($request->boolean("{$name}_clear")) {
                    $data[$name] = null;
                }

                continue;
            }
            if ($field->type === 'multiselect') {
                $many[$name] = array_map('intval', $validated[$name] ?? []);

                continue;
            }
            if ($field->type === 'password') {
                if (! empty($validated[$name])) {
                    $data[$name] = $validated[$name];
                }

                continue;
            }
            if (! array_key_exists($name, $validated) && $field->type !== 'boolean') {
                continue;
            }
            $value = $field->type === 'boolean' ? $request->boolean($name) : $this->normalise($field, $validated[$name]);
            $data[$name] = $value;
        }

        DB::transaction(function () use ($res, $record, $request, $creating, $data, $many, $validated, $wasPublished) {
            $res::beforeSave($record, $data, $request, $creating);
            $record->fill($data);
            if ($error = $this->publicationError($record)) {
                throw ValidationException::withMessages(['form' => $error]);
            }
            $record->save();
            $changes = $creating ? array_diff_key($data, array_flip(['password'])) : Audit::diff($record);
            foreach ($many as $relation => $ids) {
                $result = $record->{$relation}()->sync($ids);
                if ($result['attached'] || $result['detached']) {
                    $changes[$relation] = count($ids).' élément(s)';
                }
            }
            foreach ($res::repeaters() as $repeater) {
                if ($summary = $this->syncRepeater($record, $repeater, $validated[$repeater->relation] ?? [], $request)) {
                    $changes[$repeater->label] = $summary;
                }
            }
            $res::afterSave($record, $request, $creating);
            Audit::record($creating ? AuditAction::Create : AuditAction::Update, $record, $changes);
            $published = $res::isPublished($record);
            if ($res::$publishField && $published !== $wasPublished) {
                Audit::record($published ? AuditAction::Publish : AuditAction::Unpublish, $record);
            }
        });
    }

    private function syncRepeater(Model $record, Repeater $repeater, array $rows, Request $request): ?string
    {
        $relation = $record->{$repeater->relation}();
        $existing = $relation->get()->keyBy('id');
        $kept = [];
        $added = $updated = $deleted = 0;
        foreach (array_values($rows) as $index => $row) {
            $id = $row['id'] ?? null;
            if (! empty($row['_delete'])) {
                continue;
            }
            $attributes = [];
            foreach ($repeater->fields as $field) {
                if ($field->isUpload()) {
                    $file = $request->file("{$repeater->relation}.{$index}.{$field->name}");
                    if ($file instanceof UploadedFile) {
                        $attributes[$field->name] = Images::store($file, $repeater->relation);
                    }

                    continue;
                }
                $attributes[$field->name] = $field->type === 'boolean'
                    ? filter_var($row[$field->name] ?? false, FILTER_VALIDATE_BOOLEAN)
                    : $this->normalise($field, $row[$field->name] ?? null);
            }
            if ($id && $existing->has($id)) {
                $existing[$id]->fill($attributes);
                if ($existing[$id]->isDirty()) {
                    $existing[$id]->save();
                    $updated++;
                }
                $kept[] = (int) $id;
            } else {
                $kept[] = $relation->create($attributes)->id;
                $added++;
            }
        }
        foreach ($existing as $id => $item) {
            if (! in_array((int) $id, $kept, true)) {
                $item->delete();
                $deleted++;
            }
        }

        return ($added + $updated + $deleted) ? "{$added} ajout(s), {$updated} modif., {$deleted} suppr." : null;
    }

    /**
     * Empty input becomes NULL for foreign keys, numbers and dates, '' for text
     * and string enums (NOT NULL columns); line endings are normalised.
     */
    private function normalise(Field $field, mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace("\r\n", "\n", $value);
        }
        if ($value !== null && $value !== '') {
            return $value;
        }
        if ($field->fallback !== null) {
            return $field->fallback;
        }
        $nullable = $field->optionsModel !== null || in_array($field->type, ['number', 'date', 'datetime'], true);

        return $nullable ? null : '';
    }

    /** Business rule: case studies and testimonials need documented consent to be public. */
    private function publicationError(Model $record): ?string
    {
        if ($record instanceof CaseStudy && $record->status === PublishStatus::Published && ! $record->publication_consent && ! $record->is_example) {
            return 'Le consentement du client est requis pour publier une réalisation.';
        }
        if ($record instanceof Testimonial && $record->is_published && ! $record->consent_obtained) {
            return 'Un témoignage ne peut être publié sans consentement écrit.';
        }

        return null;
    }

    private function redirectAfterSave(string $res, Model $record, Request $request, bool $creating): RedirectResponse
    {
        $message = ucfirst($res::$label).' « '.$record.' » '.($creating ? 'créé(e)' : 'enregistré(e)').'.';
        if ($request->boolean('_continue')) {
            return redirect()->route('admin.resources.edit', [$res::$slug, $record->getKey()])->with('success', $message);
        }
        if (method_exists($res, 'redirectTo') && ($url = $res::redirectTo($record, $request))) {
            return redirect($url)->with('success', $message);
        }
        $next = $request->input('next');
        if (is_string($next) && str_starts_with($next, '/') && ! str_starts_with($next, '//')) {
            return redirect($next)->with('success', $message);
        }

        return redirect()->route('admin.resources.index', $res::$slug)->with('success', $message);
    }

    private function attributeNames(string $res): array
    {
        $names = [];
        foreach ($res::fields() as $field) {
            $names[$field->name] = mb_strtolower($field->label);
        }
        foreach ($res::repeaters() as $repeater) {
            foreach ($repeater->fields as $field) {
                $names["{$repeater->relation}.*.{$field->name}"] = mb_strtolower($field->label);
            }
        }

        return $names;
    }
}
