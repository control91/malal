<?php

namespace App\Admin;

final class Filters
{
    public static function enum(string $name, string $label, string $enum): array
    {
        return ['name' => $name, 'label' => $label, 'options' => $enum::options()];
    }

    public static function boolean(string $name, string $label): array
    {
        return ['name' => $name, 'label' => $label, 'boolean' => true, 'options' => [['value' => '1', 'label' => 'Oui'], ['value' => '0', 'label' => 'Non']]];
    }

    public static function model(string $name, string $label, string $model, string $column = 'name'): array
    {
        return ['name' => $name, 'label' => $label, 'options' => $model::query()->orderBy($column)->limit(200)->get()->map(fn ($m) => ['value' => $m->id, 'label' => (string) $m])->all()];
    }
}
