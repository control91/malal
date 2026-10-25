<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80)->default('');
            $table->string('email')->index();
            $table->string('phone', 40)->default('');
            $table->string('organization', 160)->default('');
            $table->string('job_title', 120)->default('');
            $table->string('country', 80)->default('');
            $table->string('source', 20)->default('website');
            $table->string('status', 20)->default('new')->index();
            $table->string('priority', 10)->default('normal');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->string('currency', 3)->default('GNF');
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->string('follow_up_note', 200)->default('');
            $table->text('notes')->nullable();
            $table->boolean('is_client')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->string('lost_reason', 200)->default('');
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('kind', 20)->default('contact')->index();
            $table->string('status', 20)->default('new')->index();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('organization', 160)->default('');
            $table->string('job_title', 120)->default('');
            $table->string('email')->index();
            $table->string('phone', 40)->default('');
            $table->string('country', 80)->default('');
            $table->string('subject', 200);
            $table->text('message');
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('budget', 20)->nullable();
            $table->string('timeline', 20)->nullable();
            $table->boolean('consent')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->string('source_url')->default('');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->default('');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('note');
            $table->text('content');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token', 64)->unique();
            $table->timestamp('consent_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source')->default('');
            $table->timestamps();
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('body')->default('');
            $table->string('url')->default('');
            $table->string('icon', 40)->default('bell');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label')->default('');
            $table->string('action', 20)->index();
            $table->string('object_type', 100)->default('')->index();
            $table->string('object_id', 64)->default('');
            $table->string('object_repr')->default('');
            $table->json('changes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->default('');
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('daily_page_views', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('path');
            $table->unsignedInteger('views')->default(0);
            $table->unique(['date', 'path']);
        });

        Schema::create('daily_visitors', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('visitor', 32);
            $table->unique(['date', 'visitor']);
        });
    }

    public function down(): void
    {
        foreach (['daily_visitors', 'daily_page_views', 'audit_logs', 'admin_notifications', 'newsletter_subscribers', 'activities', 'inquiries', 'leads'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
