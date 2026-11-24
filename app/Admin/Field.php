<?php

namespace App\Admin;

use App\Enums\HasOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

/**
 * Fluent description of a form field: produces both the schema sent to the Vue
 * form renderer and the Laravel validation rules (single source of truth).
 */
final class Field
{
    public bool $required = false;

    public ?int $max = null;

    public string $help = '';

    public array $extraRules = [];

    public array $options = [];

    public bool $wide = false;

    public ?string $optionsModel = null;

    public string $optionsLabel = 'name';

    public ?string $slugFrom = null;

    public string $accept = '';

    public ?int $counter = null;

    /** Value stored when the field is left empty (e.g. 0 for display order). */
    public mixed $fallback = null;

    private function __construct(public string $name, public string $label, public string $type) {}

    public static function text(string $name, string $label): self
    {
        return new self($name, $label, 'text');
    }

    public static function email(string $name, string $label): self
    {
        return (new self($name, $label, 'email'))->rules(['email:rfc']);
    }

    public static function url(string $name, string $label): self
    {
        return (new self($name, $label, 'url'))->rules(['url:http,https'])->max(300);
    }

    public static function textarea(string $name, string $label): self
    {
        return (new self($name, $label, 'textarea'))->wide();
    }

    public static function richtext(string $name, string $label): self
    {
        return (new self($name, $label, 'richtext'))->wide();
    }

    public static function number(string $name, string $label): self
    {
        return (new self($name, $label, 'number'))->rules(['numeric']);
    }

    public static function integer(string $name, string $label): self
    {
        $f = (new self($name, $label, 'number'))->rules(['integer', 'min:0']);
        $f->fallback = 0;

        return $f;
    }

    public static function boolean(string $name, string $label): self
    {
        return new self($name, $label, 'boolean');
    }

    public static function date(string $name, string $label): self
    {
        return (new self($name, $label, 'date'))->rules(['date']);
    }

    public static function datetime(string $name, string $label): self
    {
        return (new self($name, $label, 'datetime'))->rules(['date']);
    }

    public static function color(string $name, string $label): self
    {
        return (new self($name, $label, 'color'))->rules(['regex:/^#[0-9a-fA-F]{6}$/']);
    }

    public static function icon(string $name = 'icon', string $label = 'Icône'): self
    {
        return (new self($name, $label, 'icon'))->max(40);
    }

    public static function password(string $name, string $label): self
    {
        return new self($name, $label, 'password');
    }

    public static function slug(string $name = 'slug', string $label = 'Adresse (slug)', string $from = 'title'): self
    {
        $field = (new self($name, $label, 'slug'))->required()->max(160)->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']);
        $field->slugFrom = $from;
        $field->help = 'Minuscules, chiffres et tirets.';

        return $field;
    }

    /** @param class-string<\BackedEnum&HasOptions> $enum */
    public static function enum(string $name, string $label, string $enum): self
    {
        $field = new self($name, $label, 'select');
        $field->options = $enum::options();
        $field->extraRules[] = 'in:'.implode(',', $enum::values());

        return $field;
    }

    /** @param array<int, array{value: string|int, label: string}> $options */
    public static function select(string $name, string $label, array $options): self
    {
        $field = new self($name, $label, 'select');
        $field->options = $options;
        $field->extraRules[] = 'in:'.implode(',', array_column($options, 'value'));

        return $field;
    }

    /** Foreign key select fed by a model (options loaded lazily when the form is built). */
    public static function belongsTo(string $name, string $label, string $model, string $labelColumn = 'name'): self
    {
        $field = new self($name, $label, 'select');
        $field->optionsModel = $model;
        $field->optionsLabel = $labelColumn;
        $field->extraRules[] = 'integer';
        $field->extraRules[] = 'exists:'.(new $model)->getTable().',id';

        return $field;
    }

    public static function belongsToMany(string $name, string $label, string $model, string $labelColumn = 'name'): self
    {
        $field = new self($name, $label, 'multiselect');
        $field->optionsModel = $model;
        $field->optionsLabel = $labelColumn;

        return $field->wide();
    }

    public static function image(string $name, string $label): self
    {
        $field = new self($name, $label, 'image');
        $field->accept = 'image/jpeg,image/png,image/webp,image/gif';

        return $field;
    }

    /** Logo-like upload: raster images or a screened SVG. */
    public static function logo(string $name, string $label): self
    {
        $field = new self($name, $label, 'image');
        $field->accept = 'image/jpeg,image/png,image/webp,image/svg+xml';
        $field->type = 'logo';

        return $field;
    }

    public static function file(string $name, string $label): self
    {
        $field = new self($name, $label, 'file');
        $field->accept = '.pdf,.docx,.xlsx,.pptx,.txt,.csv,.zip,image/*';

        return $field;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function max(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function rules(array $rules): self
    {
        $this->extraRules = [...$this->extraRules, ...$rules];

        return $this;
    }

    public function wide(bool $wide = true): self
    {
        $this->wide = $wide;

        return $this;
    }

    public function counter(int $chars): self
    {
        $this->counter = $chars;

        return $this;
    }

    public function fallback(mixed $value): self
    {
        $this->fallback = $value;

        return $this;
    }

    public function isUpload(): bool
    {
        return in_array($this->type, ['image', 'logo', 'file'], true);
    }

    /** @return array<int, mixed> */
    public function validationRules(?Model $record = null): array
    {
        if ($this->isUpload()) {
            $rules = ['nullable', 'file'];
            $rules[] = match ($this->type) {
                'image' => 'mimes:jpg,jpeg,png,webp,gif',
                'logo' => 'mimes:jpg,jpeg,png,webp,svg',
                default => 'mimes:pdf,docx,xlsx,pptx,txt,csv,zip,jpg,jpeg,png,webp,gif',
            };
            $rules[] = 'max:'.($this->type === 'file' ? config('malal.max_file_kb') : config('malal.max_image_kb'));
            if ($this->type === 'logo') {
                $rules[] = new Rules\SafeSvg;
            }
            if ($this->type === 'image') {
                $rules[] = 'image';
            }

            return $rules;
        }
        if ($this->type === 'boolean') {
            return ['boolean'];
        }
        if ($this->type === 'multiselect') {
            return ['array'];
        }
        if ($this->type === 'password') {
            return $this->required && ! $record?->exists
                ? ['required', 'string', Password::defaults()]
                : ['nullable', 'string', Password::defaults()];
        }

        $rules = [$this->required ? 'required' : 'nullable'];
        if (in_array($this->type, ['text', 'textarea', 'richtext', 'email', 'url', 'slug', 'icon', 'color'], true)) {
            $rules[] = 'string';
        }
        if ($this->max) {
            $rules[] = 'max:'.$this->max;
        }

        return [...$rules, ...$this->extraRules];
    }

    public function schema(): array
    {
        $options = $this->options;
        if ($this->optionsModel) {
            $model = $this->optionsModel;
            $options = $model::query()->orderBy($this->optionsLabel)->limit(500)->get()
                ->map(fn ($m) => ['value' => $m->id, 'label' => (string) $m])->all();
        }

        return array_filter([
            'name' => $this->name, 'label' => $this->label, 'type' => $this->type, 'required' => $this->required,
            'max' => $this->max, 'help' => $this->help, 'options' => $options ?: null, 'wide' => $this->wide,
            'slugFrom' => $this->slugFrom, 'accept' => $this->accept ?: null, 'counter' => $this->counter,
        ], fn ($v) => $v !== null && $v !== '' && $v !== false) + ['required' => $this->required];
    }
}
