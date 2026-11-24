<?php

namespace App\Support;

use Stevebauman\Purify\Facades\Purify;

final class Html
{
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return Purify::config('rich_text')->clean($html);
    }
}
