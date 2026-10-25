<?php

namespace App\Admin;

/** Inline editing of a hasMany relation (features, section items, plan features…). */
final class Repeater
{
    /** @param array<int, Field> $fields */
    public function __construct(public string $relation, public string $label, public array $fields, public string $help = '') {}

    public function schema(): array
    {
        return ['relation' => $this->relation, 'label' => $this->label, 'help' => $this->help, 'fields' => array_map(fn (Field $f) => $f->schema(), $this->fields)];
    }

    public function rules(): array
    {
        $rules = ["{$this->relation}" => ['array'], "{$this->relation}.*.id" => ['nullable', 'integer'], "{$this->relation}.*._delete" => ['nullable', 'boolean']];
        foreach ($this->fields as $field) {
            $rules["{$this->relation}.*.{$field->name}"] = $field->validationRules();
        }

        return $rules;
    }
}
