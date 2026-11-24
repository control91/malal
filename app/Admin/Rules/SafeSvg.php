<?php

namespace App\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/** Rejects SVG files containing scripts, event handlers or external references. */
class SafeSvg implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || strtolower($value->getClientOriginalExtension()) !== 'svg') {
            return;
        }
        $content = strtolower((string) file_get_contents($value->getRealPath()));
        $forbidden = ['<script', 'javascript:', 'onload=', 'onerror=', 'onclick=', '<foreignobject', 'xlink:href="http', '<iframe', '<embed'];
        if (! str_contains($content, '<svg') || array_filter($forbidden, fn ($t) => str_contains($content, $t))) {
            $fail('Ce fichier SVG contient des éléments non autorisés.');
        }
    }
}
