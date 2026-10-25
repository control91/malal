<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 120)->default('MALAL');
            $table->string('legal_name', 200)->default('');
            $table->string('tagline', 200)->default("Cloud, SaaS & Intelligence Artificielle pour l'Afrique");
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('favicon')->nullable();
            $table->string('registration_number', 80)->default('');
            $table->string('tax_number', 80)->default('');
            $table->string('color_primary', 7)->default('#1167B1');
            $table->string('color_accent', 7)->default('#22D3EE');
            $table->string('color_ink', 7)->default('#0A1330');
            $table->string('email_contact')->default('contact@example.com');
            $table->string('email_sales')->default('');
            $table->string('email_support')->default('');
            $table->string('phone', 40)->default('');
            $table->string('whatsapp', 40)->default('');
            $table->string('address')->default('');
            $table->string('city', 80)->default('Conakry');
            $table->string('country', 80)->default('Guinée');
            $table->string('opening_hours', 120)->default('');
            $table->string('map_embed_url', 500)->default('');
            foreach (['linkedin', 'x', 'facebook', 'instagram', 'youtube', 'github'] as $network) {
                $table->string("{$network}_url", 300)->default('');
            }
            $table->string('meta_title', 70)->default('');
            $table->string('meta_description', 170)->default('');
            $table->string('og_image')->nullable();
            $table->string('twitter_handle', 40)->default('');
            $table->text('hosting_disclosure')->nullable();
            $table->text('footer_text')->nullable();
            $table->string('announcement', 200)->default('');
            $table->string('announcement_url')->default('');
            $table->string('enabled_languages', 60)->default('fr');
            $table->text('notification_emails')->nullable();
            $table->boolean('notify_on_inquiry')->default(true);
            $table->boolean('send_acknowledgement')->default(true);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 120)->unique();
            $table->string('template', 20)->default('standard');
            $table->string('eyebrow', 120)->default('');
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_system')->default(false);
            $this->seo($table);
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('text');
            $table->string('theme', 10)->default('light');
            $table->string('anchor', 60)->default('');
            $table->string('eyebrow', 120)->default('');
            $table->string('title', 200)->default('');
            $table->text('subtitle')->nullable();
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->string('image_alt', 200)->default('');
            $table->string('cta_label', 60)->default('');
            $table->string('cta_url')->default('');
            $table->string('cta2_label', 60)->default('');
            $table->string('cta2_url')->default('');
            $table->unsignedSmallInteger('limit')->default(6);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_section_id')->constrained()->cascadeOnDelete();
            $table->string('icon', 40)->default('');
            $table->string('title', 160);
            $table->text('text')->nullable();
            $table->string('badge', 60)->default('');
            $table->string('progress', 20)->default('');
            $table->string('link_url')->default('');
            $table->string('link_label', 60)->default('');
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('title', 80)->default('');
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('url');
            $table->string('description', 140)->default('');
            $table->string('icon', 40)->default('');
            $table->string('style', 10)->default('link');
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role', 120);
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->string('linkedin_url', 300)->default('');
            $table->boolean('is_leadership')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('kind', 20)->default('technology');
            $table->string('logo')->nullable();
            $table->string('url', 300)->default('');
            $table->text('description')->nullable();
            $table->boolean('agreement_confirmed')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 160)->unique();
            $table->string('department', 80)->default('');
            $table->string('location', 80)->default('Conakry');
            $table->string('contract', 20)->default('cdi');
            $table->boolean('remote_possible')->default(false);
            $table->string('summary')->default('');
            $table->longText('description');
            $table->boolean('is_open')->default(true);
            $table->date('closes_on')->nullable();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('file');
            $table->string('alt_text', 200)->default('');
            $table->unsignedInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    private function seo(Blueprint $table): void
    {
        $table->string('meta_title', 70)->default('');
        $table->string('meta_description', 170)->default('');
        $table->string('og_image')->nullable();
        $table->boolean('noindex')->default(false);
    }

    public function down(): void
    {
        foreach (['media_assets', 'job_openings', 'partners', 'team_members', 'navigation_items', 'navigation_menus', 'section_items', 'page_sections', 'pages', 'site_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
