<?php

use App\Enums\Role;
use App\Mail\TextMail;
use App\Models\AuditLog;
use App\Models\BlogArticle;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => seedSite());

it('requires authentication', function () {
    $this->get('/admin')->assertRedirect('/connexion');
});

it('logs out deactivated accounts', function () {
    $this->actingAs(staff(Role::Admin, ['is_active' => false]))->get('/admin')->assertRedirect('/connexion');
});

it('enforces the role matrix server-side', function (Role $role, string $url, int $status) {
    $this->actingAs(staff($role))->get($url)->assertStatus($status);
})->with([
    [Role::Editor, '/admin/articles', 200], [Role::Editor, '/admin/demandes', 403], [Role::Editor, '/admin/parametres', 403], [Role::Editor, '/admin/utilisateurs', 403],
    [Role::Sales, '/admin/prospects', 200], [Role::Sales, '/admin/pipeline', 200], [Role::Sales, '/admin/pages', 403], [Role::Sales, '/admin/audit', 403],
    [Role::Support, '/admin/demandes', 200], [Role::Support, '/admin/prospects/nouveau', 403],
    [Role::Viewer, '/admin/articles', 200], [Role::Viewer, '/admin/articles/nouveau', 403], [Role::Viewer, '/admin/utilisateurs', 403],
    [Role::Admin, '/admin/utilisateurs', 200], [Role::Admin, '/admin/audit', 200], [Role::Admin, '/admin/parametres', 200],
]);

it('refuses writes from a read-only role', function () {
    $page = Page::where('slug', 'entreprise')->first();
    $this->actingAs(staff(Role::Viewer))->post("/admin/pages/{$page->id}", ['title' => 'Hacked', 'slug' => 'entreprise', 'template' => 'standard', 'status' => 'published'])->assertForbidden();
    expect($page->fresh()->title)->not->toBe('Hacked');
});

it('lets an editor create an article, sanitised and audited', function () {
    $editor = staff(Role::Editor);
    $this->actingAs($editor)->post('/admin/articles', [
        'title' => 'Nouvel article', 'slug' => 'nouvel-article', 'excerpt' => '', 'body' => '<p>Corps</p><script>x</script>', 'status' => 'published', 'published_at' => '', 'tags' => [],
    ])->assertRedirect('/admin/articles');
    $article = BlogArticle::where('slug', 'nouvel-article')->sole();
    expect($article->author_id)->toBe($editor->id)->and($article->isLive())->toBeTrue()->and($article->body)->not->toContain('<script');
    expect(AuditLog::where('object_type', 'BlogArticle')->pluck('action')->map->value->all())->toContain('create', 'publish');
});

it('toggles publication and deletes with an audit trail', function () {
    $this->actingAs(staff(Role::Editor));
    $article = BlogArticle::create(['title' => 'A', 'slug' => 'a', 'body' => '<p>a</p>']);
    $this->post("/admin/articles/{$article->id}/publication");
    expect($article->fresh()->status->value)->toBe('published');
    $this->delete("/admin/articles/{$article->id}");
    expect(BlogArticle::find($article->id))->toBeNull()->and(AuditLog::where('action', 'delete')->where('object_repr', 'A')->exists())->toBeTrue();
});

it('protects system pages from deletion', function () {
    $page = Page::where('slug', 'accueil')->first();
    $this->actingAs(staff(Role::Admin))->delete("/admin/pages/{$page->id}");
    expect(Page::find($page->id))->not->toBeNull();
});

it('edits a section and syncs its inline items', function () {
    $section = PageSection::whereHas('page', fn ($q) => $q->where('slug', 'accueil'))->where('kind', 'hero')->with('items')->first();
    $items = $section->items->map(fn ($i) => ['id' => $i->id, 'order' => $i->order, 'icon' => $i->icon, 'title' => $i->title, 'text' => '', 'badge' => '', 'progress' => '', 'link_label' => '', 'link_url' => ''])->all();
    $items[0]['_delete'] = true;
    $items[] = ['order' => 99, 'icon' => 'cloud', 'title' => 'Nouvel élément', 'text' => '', 'badge' => '', 'progress' => '', 'link_label' => '', 'link_url' => ''];

    $this->actingAs(staff(Role::Editor))->post("/admin/sections/{$section->id}", [
        'page_id' => $section->page_id, 'kind' => 'hero', 'theme' => 'dark', 'order' => 0, 'is_active' => true, 'anchor' => '', 'eyebrow' => 'E',
        'title' => 'Nouveau titre', 'subtitle' => 'S', 'body' => '', 'image_alt' => '', 'cta_label' => 'Go', 'cta_url' => '/solutions',
        'cta2_label' => '', 'cta2_url' => '', 'limit' => 6, 'items' => $items,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $titles = $section->fresh()->items->pluck('title')->all();
    expect($section->fresh()->title)->toBe('Nouveau titre')->and($titles)->toContain('Nouvel élément')->not->toContain($section->items[0]->title);
});

it('validates and audits the site settings', function () {
    $this->actingAs(staff(Role::Admin));
    $values = collect(SiteSetting::current()->fresh()->getAttributes())->except(['id', 'logo', 'logo_dark', 'favicon', 'og_image', 'created_at', 'updated_at'])->map(fn ($v) => $v ?? '')->all();
    $this->post('/admin/parametres', ['company_name' => 'Nouvelle Marque', 'enabled_languages' => 'fr,en', 'color_primary' => '#123456'] + $values)->assertSessionHasNoErrors();
    expect(SiteSetting::current()->company_name)->toBe('Nouvelle Marque')->and(AuditLog::where('action', 'settings')->exists())->toBeTrue();
    $this->post('/admin/parametres', ['color_primary' => 'red'] + $values)->assertSessionHasErrors('color_primary');
});

it('prevents an admin from creating or editing a super administrator', function () {
    $this->actingAs(staff(Role::Admin));
    $this->post('/admin/utilisateurs', ['email' => 'x@test.local', 'role' => 'superadmin', 'is_active' => true, 'password' => 'Another-str0ng-pass!'])->assertSessionHasErrors('role');
    expect(User::where('email', 'x@test.local')->exists())->toBeFalse();

    $boss = staff(Role::Superadmin, ['email' => 'boss@test.local']);
    $this->post("/admin/utilisateurs/{$boss->id}", ['email' => 'boss@test.local', 'role' => 'viewer', 'is_active' => true])->assertSessionHasErrors('form');
    expect($boss->fresh()->role)->toBe(Role::Superadmin);
});

it('audits role changes', function () {
    $this->actingAs(staff(Role::Admin));
    $target = staff(Role::Viewer, ['email' => 't@test.local']);
    $this->post("/admin/utilisateurs/{$target->id}", ['email' => 't@test.local', 'role' => 'editor', 'is_active' => true])->assertSessionHasNoErrors();
    expect($target->fresh()->role)->toBe(Role::Editor)->and(AuditLog::where('action', 'role_change')->where('object_id', (string) $target->id)->exists())->toBeTrue();
});

it('handles the inquiry workflow', function () {
    Mail::fake();
    $sales = staff(Role::Sales);
    $lead = Lead::create(['first_name' => 'A', 'email' => 'a@example.com']);
    $inquiry = Inquiry::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'subject' => 'S', 'message' => 'M', 'lead_id' => $lead->id]);
    $this->actingAs($sales);
    $this->patch("/admin/demandes/{$inquiry->id}", ['status' => 'in_progress', 'assigned_to_id' => $sales->id]);
    $this->post("/admin/demandes/{$inquiry->id}/commentaire", ['kind' => 'note', 'content' => 'Rappeler lundi']);
    $this->post("/admin/demandes/{$inquiry->id}/reponse", ['subject' => 'Re', 'message' => 'Bonjour', 'mark_answered' => true]);

    $inquiry->refresh();
    expect($inquiry->status->value)->toBe('answered')->and($inquiry->assigned_to_id)->toBe($sales->id)->and($inquiry->answered_at)->not->toBeNull();
    Mail::assertQueued(TextMail::class, fn ($m) => $m->hasTo('a@example.com'));
    expect($inquiry->activities->pluck('kind')->map->value->all())->toContain('status', 'assignment', 'note', 'email');
    expect(AuditLog::pluck('action')->map->value->all())->toContain('status', 'assign', 'email');
});

it('refuses lead status changes from support', function () {
    $lead = Lead::create(['first_name' => 'A', 'email' => 'a@example.com']);
    $this->actingAs(staff(Role::Support))->post("/admin/prospects/{$lead->id}/statut", ['status' => 'won'])->assertForbidden();
});

it('converts a lead into a client', function () {
    $lead = Lead::create(['first_name' => 'A', 'email' => 'a@example.com']);
    $this->actingAs(staff(Role::Sales))->post("/admin/prospects/{$lead->id}/convertir");
    $lead->refresh();
    expect($lead->is_client)->toBeTrue()->and($lead->status->value)->toBe('won')->and($lead->converted_at)->not->toBeNull();
    expect(AuditLog::where('action', 'convert')->exists())->toBeTrue();
});

it('guards CSV exports against formula injection', function () {
    $this->actingAs(staff(Role::Sales));
    $lead = Lead::create(['first_name' => '=HYPERLINK(1)', 'email' => 'a@example.com']);
    Inquiry::create(['first_name' => '=cmd', 'last_name' => 'B', 'email' => 'a@example.com', 'subject' => 'S', 'message' => 'M', 'lead_id' => $lead->id]);
    expect($this->get('/admin/demandes/export.csv')->assertOk()->streamedContent())->toContain("'=cmd");
    expect(AuditLog::where('action', 'export')->exists())->toBeTrue();
});

it('audits logins and locks out after repeated failures', function () {
    staff(Role::Admin, ['email' => 'lock@test.local']);
    foreach (range(1, 5) as $i) {
        $this->post('/connexion', ['email' => 'lock@test.local', 'password' => 'wrong']);
    }
    expect(AuditLog::where('action', 'login_failed')->count())->toBeGreaterThanOrEqual(5);
    $this->post('/connexion', ['email' => 'lock@test.local', 'password' => 'Str0ng-pass-for-tests!'])->assertSessionHasErrors('email');
    $this->assertGuest();
});
