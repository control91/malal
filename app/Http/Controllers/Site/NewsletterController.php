<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\SiteSetting;
use App\Services\Notifier;
use App\Support\AntiSpam;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Double opt-in newsletter. */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'consent' => ['accepted'],
        ], ['consent.accepted' => 'Merci d\'accepter de recevoir la newsletter.']);
        if ($error = AntiSpam::check($request->input('website'), $request->input('form_token'))) {
            return back()->withErrors(['newsletter' => $error]);
        }

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower($data['email'])],
            ['source' => mb_substr((string) $request->headers->get('referer'), 0, 255), 'consent_at' => now()],
        );
        if ($subscriber->unsubscribed_at) {
            $subscriber->update(['unsubscribed_at' => null, 'confirmed_at' => null, 'consent_at' => now()]);
        }
        if (! $subscriber->confirmed_at) {
            $site = SiteSetting::current();
            Notifier::mail([$subscriber->email], "{$site->company_name} — confirmez votre inscription", 'newsletter_confirm', [
                'site' => $site, 'url' => route('newsletter.confirm', $subscriber->token),
            ]);
        }

        return back()->with('success', 'Merci ! Un email de confirmation vient de vous être envoyé.');
    }

    public function confirm(string $token): Response
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();
        if (! $subscriber->confirmed_at) {
            $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);
        }

        return Inertia::render('Site/Newsletter', ['state' => 'confirmed', 'email' => $subscriber->email, 'token' => $token])
            ->with('seo', Seo::make('Inscription confirmée', noindex: true));
    }

    public function unsubscribe(Request $request, string $token): Response
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $state = 'confirm_unsubscribe';
        if ($request->isMethod('post')) {
            $subscriber->update(['unsubscribed_at' => now()]);
            $state = 'unsubscribed';
        }

        return Inertia::render('Site/Newsletter', ['state' => $state, 'email' => $subscriber->email, 'token' => $token])
            ->with('seo', Seo::make('Newsletter', noindex: true));
    }
}
