<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Content-Security-Policy: only first-party scripts (with a per-request nonce for
 * the Vite tags), no third-party origin. Vue templates are pre-compiled at build
 * time, so 'unsafe-eval' is not needed. Inline style attributes stay allowed
 * (dynamic Vue :style bindings, brand colour variables).
 */
class AppPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME, [Keyword::SELF, 'https://www.openstreetmap.org'])
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::IMG, array_filter([Keyword::SELF, 'data:', 'blob:', env('CSP_IMG_EXTRA')]))
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE]);
    }
}
