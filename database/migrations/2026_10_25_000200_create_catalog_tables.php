<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('icon', 40)->default('');
            $table->string('tagline', 200)->default('');
            $table->text('summary')->nullable();
            $table->string('hero_title', 200)->default('');
            $table->text('hero_subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('show_hosting_disclosure')->default(false);
            $table->boolean('demo_space_enabled')->default(false);
            $table->boolean('is_published')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->string('meta_title', 70)->default('');
            $table->string('meta_description', 170)->default('');
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 160);
            $table->string('icon', 40)->default('');
            $table->text('summary');
            $table->text('details')->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->unique(['service_category_id', 'slug']);
        });

        Schema::create('status_components', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description')->default('');
            $table->string('state', 20)->default('operational');
            $table->text('message')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->longText('answer');
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('icon', 40)->default('');
            $table->string('tagline', 200);
            $table->text('summary');
            $table->longText('description')->nullable();
            $table->text('benefits')->nullable();
            $table->string('audience')->default('');
            $table->string('logo')->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->boolean('is_example')->default(false);
            $table->boolean('demo_enabled')->default(true);
            $table->boolean('show_pricing')->default(true);
            $table->string('external_url', 300)->default('');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->string('meta_title', 70)->default('');
            $table->string('meta_description', 170)->default('');
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });

        Schema::create('product_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('icon', 40)->default('');
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('product_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image');
            $table->string('caption', 200)->default('');
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('pricing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description')->default('');
            $table->string('currency', 3)->default('GNF');
            $table->decimal('price_monthly', 14, 2)->nullable();
            $table->decimal('price_yearly', 14, 2)->nullable();
            $table->boolean('price_on_request')->default(false);
            $table->string('price_note', 120)->default('');
            $table->string('status', 20)->default('available');
            $table->boolean('is_highlighted')->default(false);
            $table->string('cta_label', 60)->default('Demander un devis');
            $table->string('cta_url')->default('');
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('pricing_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_plan_id')->constrained()->cascadeOnDelete();
            $table->string('label', 160);
            $table->string('value', 80)->default('');
            $table->boolean('included')->default(true);
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('pricing_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description')->default('');
            $table->string('price_label', 80)->default('');
            $table->unsignedInteger('order')->default(0);
        });
    }

    public function down(): void
    {
        foreach (['pricing_options', 'pricing_features', 'pricing_plans', 'pricing_groups', 'product_screenshots', 'product_features', 'products', 'faqs', 'status_components', 'services', 'service_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
