<?php

use App\Enums\Role;
use App\Mail\TextMail;
use App\Models\Activity;
use App\Models\AdminNotification;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    seedSite();
    Mail::fake();
});

function contactPayload(array $extra = []): array
{
    return $extra + [
        'kind' => 'contact', 'first_name' => 'Awa', 'last_name' => 'Test', 'organization' => 'Org', 'email' => 'awa@example.com',
        'phone' => '', 'subject' => 'Question', 'message' => 'Bonjour, une question.', 'consent' => true, 'website' => '', 'form_token' => formToken(),
    ];
}

it('creates an inquiry, a lead, an activity, notifications and emails', function () {
    $sales = staff(Role::Sales);
    staff(Role::Editor);

    $this->post('/contact', contactPayload())->assertRedirect('/merci');

    $inquiry = Inquiry::sole();
    expect($inquiry->consent)->toBeTrue()->and($inquiry->consent_at)->not->toBeNull()->and($inquiry->reference)->toStartWith('CON-');
    expect($inquiry->lead->email)->toBe('awa@example.com');
    expect(Activity::where('inquiry_id', $inquiry->id)->where('kind', 'inquiry')->exists())->toBeTrue();
    // Only roles holding inquiries.view are notified (sales yes, editor no).
    expect(AdminNotification::pluck('user_id')->all())->toBe([$sales->id]);
    Mail::assertQueued(TextMail::class, fn ($m) => str_contains($m->mailSubject, $inquiry->reference));
    Mail::assertQueued(TextMail::class, fn ($m) => str_contains($m->mailSubject, 'bien reçu'));
    $this->get('/merci')->assertInertia(fn ($page) => $page->where('reference', $inquiry->reference));
});

it('reuses the existing lead for a second request', function () {
    $this->post('/contact', contactPayload());
    $this->post('/contact', contactPayload(['email' => 'AWA@example.com', 'subject' => 'Autre', 'form_token' => formToken()]));
    expect(Lead::count())->toBe(1)->and(Inquiry::count())->toBe(2);
});

it('requires consent', function () {
    $this->post('/contact', contactPayload(['consent' => false]))->assertSessionHasErrors('consent');
    expect(Inquiry::count())->toBe(0);
});

it('blocks the honeypot', function () {
    $this->post('/contact', contactPayload(['website' => 'http://spam']))->assertSessionHasErrors('form');
    expect(Inquiry::count())->toBe(0);
});

it('rejects too fast submissions', function () {
    config(['malal.form_min_seconds' => 5]);
    $this->post('/contact', contactPayload(['form_token' => formToken(0)]))->assertSessionHasErrors('form');
    expect(Inquiry::count())->toBe(0);
});

it('rejects a forged token', function () {
    $this->post('/contact', contactPayload(['form_token' => 'forged']))->assertSessionHasErrors('form');
});

it('rate limits submissions per IP', function () {
    config(['malal.form_rate_limit' => 2]);
    foreach ([1, 2] as $i) {
        $this->post('/contact', contactPayload(['subject' => "s$i", 'form_token' => formToken()]))->assertRedirect('/merci');
    }
    $this->post('/contact', contactPayload(['subject' => 's3', 'form_token' => formToken()]))->assertSessionHas('error');
    expect(Inquiry::count())->toBe(2);
});

it('requires a product for demo requests', function () {
    $this->post('/demo', contactPayload())->assertSessionHasErrors('product_id');
});

it('runs the newsletter double opt-in', function () {
    $this->post('/newsletter', ['email' => 'nl@example.com', 'consent' => true, 'form_token' => formToken()])->assertSessionHas('success');
    $subscriber = NewsletterSubscriber::sole();
    expect($subscriber->is_active)->toBeFalse();
    Mail::assertQueued(TextMail::class, 1);

    $this->get("/newsletter/confirmer/{$subscriber->token}")->assertOk();
    expect($subscriber->fresh()->is_active)->toBeTrue();
    $this->post("/newsletter/desinscription/{$subscriber->token}")->assertOk();
    expect($subscriber->fresh()->is_active)->toBeFalse();
});
