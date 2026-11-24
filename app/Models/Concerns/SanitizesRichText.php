<?php

namespace App\Models\Concerns;

use App\Support\Html;

/**
 * Cleans the HTML of the attributes listed in $richText every time the model
 * is saved (defence in depth: the editor output is never trusted).
 */
trait SanitizesRichText
{
    public static function bootSanitizesRichText(): void
    {
        static::saving(function ($model) {
            foreach ($model->richText ?? [] as $attribute) {
                if ($model->isDirty($attribute)) {
                    $model->{$attribute} = Html::clean($model->{$attribute});
                }
            }
        });
    }
}
