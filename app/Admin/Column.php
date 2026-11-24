<?php

namespace App\Admin;

/** A list column. type: text | title | badge | bool | date | datetime | image | money | muted */
final class Column
{
    public function __construct(public string $key, public string $label, public string $type = 'text', public ?string $sort = null) {}

    public function schema(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'type' => $this->type, 'sort' => $this->sort];
    }
}
