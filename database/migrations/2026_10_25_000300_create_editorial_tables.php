<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
        });

        Schema::create('blog_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('cover')->nullable();
            $table->string('cover_alt', 200)->default('');
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title', 70)->default('');
            $table->string('meta_description', 170)->default('');
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });

        Schema::create('blog_article_blog_tag', function (Blueprint $table) {
            $table->foreignId('blog_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_article_id', 'blog_tag_id']);
        });

        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->string('client_name', 160)->default('');
            $table->boolean('show_client_name')->default(false);
            $table->string('sector', 120);
            $table->string('summary');
            $table->longText('challenge');
            $table->longText('solution');
            $table->longText('results')->nullable();
            $table->string('technologies')->default('');
            $table->string('cover')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('publication_consent')->default(false);
            $table->string('consent_reference', 200)->default('');
            $table->boolean('is_example')->default(false);
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('order')->default(0);
            $table->string('meta_title', 70)->default('');
            $table->string('meta_description', 170)->default('');
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });

        Schema::create('case_study_service_category', function (Blueprint $table) {
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['case_study_id', 'service_category_id']);
        });

        Schema::create('case_study_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->string('image');
            $table->string('caption', 200)->default('');
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 120);
            $table->string('author_role', 120)->default('');
            $table->string('organization', 160)->default('');
            $table->text('quote');
            $table->string('photo')->nullable();
            $table->foreignId('case_study_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('consent_obtained')->default(false);
            $table->string('consent_reference', 200)->default('');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['testimonials', 'case_study_images', 'case_study_service_category', 'case_studies', 'blog_article_blog_tag', 'blog_articles', 'blog_tags', 'blog_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
