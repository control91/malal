<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Anti-spam for public forms without third-party CAPTCHA (privacy friendly):
 * a honeypot field, an encrypted timestamp (too-fast submissions are bots)
 * and a per-IP rate limiter ("public-forms", see AppServiceProvider).
 */
final class AntiSpam
{
    public static function token(): string
    {
        return Crypt::encryptString((string) time());
    }

    /** Returns null when the submission looks human, otherwise an error message. */
    public static function check(?string $honeypot, ?string $token): ?string
    {
        if (filled($honeypot)) {
            return "Votre demande n'a pas pu être envoyée.";
        }
        try {
            $issued = (int) Crypt::decryptString((string) $token);
        } catch (\Throwable) {
            return 'Le formulaire a expiré. Merci de recharger la page.';
        }
        $age = time() - $issued;
        if ($age > 86400) {
            return 'Le formulaire a expiré. Merci de recharger la page.';
        }
        if ($age < (int) config('malal.form_min_seconds')) {
            return "Votre demande n'a pas pu être envoyée. Merci de réessayer.";
        }

        return null;
    }
}
