<?php

use App\Admin\Rules\SafeSvg;
use App\Enums\Role;
use App\Models\CaseStudy;
use App\Models\MediaAsset;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

it('seeds the site idempotently without overwriting edits', function () {
    $this->artisan('app:seed-site')->assertSuccessful();
    $hero = PageSection::where('kind', 'hero')->first();
    $hero->update(['title' => 'Edité']);
    $count = Service::count();
    $this->artisan('app:seed-site')->assertSuccessful();
    expect(Service::count())->toBe($count)->and($hero->fresh()->title)->toBe('Edité');
});

it('creates a super administrator from the environment', function () {
    putenv('ADMIN_PASSWORD=Very-str0ng-admin-pass1');
    $this->artisan('app:create-admin', ['--email' => 'Root@Test.local', '--no-interaction' => true])->assertSuccessful();
    $user = User::where('email', 'root@test.local')->sole();
    expect($user->role)->toBe(Role::Superadmin)->and(Hash::check('Very-str0ng-admin-pass1', $user->password))->toBeTrue();
    $this->artisan('app:create-admin', ['--email' => 'root@test.local', '--no-interaction' => true])->assertFailed();
    putenv('ADMIN_PASSWORD');
});

it('rejects weak admin passwords', function () {
    putenv('ADMIN_PASSWORD=123');
    $this->artisan('app:create-admin', ['--email' => 'weak@test.local', '--no-interaction' => true])->assertFailed();
    putenv('ADMIN_PASSWORD');
});

it('refuses to seed demo data in production', function () {
    app()->detectEnvironment(fn () => 'production');
    $this->artisan('app:seed-demo')->assertFailed();
});

it('rejects fake images and dangerous SVG uploads', function () {
    seedSite();
    $this->actingAs(staff(Role::Editor));
    // A real temporary file so that the MIME type is detected from the content, not the name.
    $path = tempnam(sys_get_temp_dir(), 'evil');
    file_put_contents($path, '<?php echo 1; ?>');
    $this->post('/admin/equipe', ['name' => 'A', 'role' => 'B', 'photo' => new UploadedFile($path, 'evil.png', null, null, true)])->assertSessionHasErrors('photo');
    $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg onload="alert(1)"><script>1</script></svg>');
    expect(Validator::make(['logo' => $svg], ['logo' => [new SafeSvg]])->fails())->toBeTrue();
});

it('resizes and converts uploaded images to WebP', function () {
    Storage::fake('media');
    seedSite();
    $this->actingAs(staff(Role::Editor));
    $this->post('/admin/equipe', ['name' => 'A', 'role' => 'B', 'order' => 0, 'photo' => UploadedFile::fake()->image('photo.png', 3000, 1500)])->assertSessionHasNoErrors();
    $member = TeamMember::sole();
    expect($member->photo)->toEndWith('.webp');
    [$width] = getimagesizefromstring(Storage::disk('media')->get($member->photo));
    expect($width)->toBeLessThanOrEqual(2000);
});

it('uploads images inserted in rich text and keeps them in the saved content', function () {
    Storage::fake('media');
    $this->actingAs(staff(Role::Editor));

    $response = $this->postJson('/admin/realisations/image-editeur', ['image' => UploadedFile::fake()->image('ecran.png', 1200, 800), 'alt' => "Écran d'accueil"])
        ->assertOk()->assertJsonPath('alt', "Écran d'accueil");
    $url = $response->json('url');
    expect($url)->toEndWith('.webp');
    expect(MediaAsset::where('title', 'ecran')->where('alt_text', "Écran d'accueil")->exists())->toBeTrue();

    $case = CaseStudy::create(['title' => 'P', 'slug' => 'p', 'sector' => 'S', 'summary' => 's', 'body' => "<p>Avant</p><img src=\"$url\" alt=\"Écran\" onerror=\"alert(1)\">"]);
    expect($case->fresh()->body)->toContain('<img src="'.$url.'"')->not->toContain('onerror');
});

it('restricts rich text image uploads to people who can edit the resource', function () {
    Storage::fake('media');
    $image = fn () => UploadedFile::fake()->image('a.png');

    $this->actingAs(staff(Role::Viewer))->postJson('/admin/realisations/image-editeur', ['image' => $image()])->assertForbidden();
    $this->actingAs(staff(Role::Sales))->postJson('/admin/articles/image-editeur', ['image' => $image()])->assertForbidden();
    $this->actingAs(staff(Role::Editor))->postJson('/admin/partenaires/image-editeur', ['image' => $image()])->assertNotFound();
    $this->actingAs(staff(Role::Editor))->postJson('/admin/articles/image-editeur', ['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])->assertJsonValidationErrors('image');
});
