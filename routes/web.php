<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site (French URLs; /en/ can be added later with a locale prefix)
|--------------------------------------------------------------------------
*/
Route::get('robots.txt', [Site\SeoController::class, 'robots']);
Route::get('sitemap.xml', [Site\SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/', [Site\PageController::class, 'home'])->name('home');
Route::get('expertises/{slug}', [Site\ExpertiseController::class, 'show'])->name('expertise');
Route::get('solutions', [Site\ProductController::class, 'index'])->name('solutions.index');
Route::get('solutions/{slug}', [Site\ProductController::class, 'show'])->name('solutions.show');
Route::get('tarifs', [Site\PricingController::class, 'index'])->name('pricing');
Route::get('blog', [Site\BlogController::class, 'index'])->name('blog.index');
Route::get('blog/categorie/{slug}', [Site\BlogController::class, 'category'])->name('blog.category');
Route::get('blog/etiquette/{slug}', [Site\BlogController::class, 'tag'])->name('blog.tag');
Route::get('blog/{slug}', [Site\BlogController::class, 'show'])->name('blog.show');
Route::get('realisations', [Site\CaseStudyController::class, 'index'])->name('cases.index');
Route::get('realisations/{slug}', [Site\CaseStudyController::class, 'show'])->name('cases.show');
Route::get('faq', [Site\PageController::class, 'faq'])->name('faq');
Route::get('carrieres', [Site\PageController::class, 'careers'])->name('careers');
Route::get('carrieres/{slug}', [Site\PageController::class, 'job'])->name('careers.show');
Route::get('partenaires', [Site\PageController::class, 'partners'])->name('partners');
Route::get('statut', [Site\PageController::class, 'status'])->name('status');

Route::get('contact', [Site\InquiryController::class, 'contact'])->name('contact');
Route::get('devis', [Site\InquiryController::class, 'quote'])->name('quote');
Route::get('demo', [Site\InquiryController::class, 'demo'])->name('demo');
Route::middleware('throttle:public-forms')->group(function () {
    Route::post('contact', [Site\InquiryController::class, 'storeContact'])->name('contact.store');
    Route::post('devis', [Site\InquiryController::class, 'storeQuote'])->name('quote.store');
    Route::post('demo', [Site\InquiryController::class, 'storeDemo'])->name('demo.store');
    Route::post('newsletter', [Site\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
});
Route::get('merci', [Site\InquiryController::class, 'thanks'])->name('thanks');
Route::get('newsletter/confirmer/{token}', [Site\NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::match(['get', 'post'], 'newsletter/desinscription/{token}', [Site\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('connexion', [LoginController::class, 'create'])->name('login');
    Route::post('connexion', [LoginController::class, 'store'])->name('login.store');
    Route::get('mot-de-passe/oublie', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('mot-de-passe/oublie', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('mot-de-passe/reinitialiser/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('mot-de-passe/reinitialiser', [PasswordResetController::class, 'update'])->name('password.update');
});
Route::post('deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Back-office — every route checks a capability server-side ("can:<area>.<view|edit>")
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['admin.ip', 'auth', 'active'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('recherche', [Admin\SearchController::class, 'index'])->name('search');

    Route::pattern('inquiry', '[0-9]+');
    Route::pattern('lead', '[0-9]+');
    Route::get('demandes/{inquiry}', [Admin\InquiryController::class, 'show'])->middleware('can:inquiries.view')->name('inquiries.show');
    Route::middleware('can:inquiries.edit')->group(function () {
        Route::patch('demandes/{inquiry}', [Admin\InquiryController::class, 'update'])->name('inquiries.update');
        Route::post('demandes/{inquiry}/commentaire', [Admin\InquiryController::class, 'note'])->name('inquiries.note');
        Route::post('demandes/{inquiry}/reponse', [Admin\InquiryController::class, 'reply'])->name('inquiries.reply');
    });

    Route::middleware('can:crm.view')->group(function () {
        Route::get('prospects/{lead}/fiche', [Admin\LeadController::class, 'show'])->name('leads.show');
        Route::get('pipeline', [Admin\LeadController::class, 'pipeline'])->name('pipeline');
        Route::get('relances', [Admin\LeadController::class, 'followups'])->name('followups');
    });
    Route::middleware('can:crm.edit')->group(function () {
        Route::post('prospects/{lead}/statut', [Admin\LeadController::class, 'status'])->name('leads.status');
        Route::post('prospects/{lead}/interaction', [Admin\LeadController::class, 'note'])->name('leads.note');
        Route::post('prospects/{lead}/relance', [Admin\LeadController::class, 'followup'])->name('leads.followup');
        Route::post('prospects/{lead}/convertir', [Admin\LeadController::class, 'convert'])->name('leads.convert');
    });

    Route::post('sections/{section}/deplacer/{direction}', [Admin\PageSectionController::class, 'move'])->middleware('can:content.edit')->whereIn('direction', ['up', 'down'])->name('sections.move');

    Route::get('parametres', [Admin\SettingsController::class, 'edit'])->middleware('can:settings.view')->name('settings.edit');
    Route::post('parametres', [Admin\SettingsController::class, 'update'])->middleware('can:settings.edit')->name('settings.update');
    Route::get('statistiques', [Admin\AnalyticsController::class, 'index'])->middleware('can:analytics.view')->name('analytics');

    Route::get('profil', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profil', [Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profil/mot-de-passe', [Admin\ProfileController::class, 'password'])->name('profile.password');

    Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/menu', [Admin\NotificationController::class, 'menu'])->name('notifications.menu');
    Route::post('notifications/tout-lire', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/lire', [Admin\NotificationController::class, 'read'])->name('notifications.read');

    // Generic CRUD for every registered resource (see app/Admin/Resources).
    Route::get('{resource}', [Admin\ResourceController::class, 'index'])->name('resources.index');
    Route::get('{resource}/nouveau', [Admin\ResourceController::class, 'create'])->name('resources.create');
    Route::post('{resource}', [Admin\ResourceController::class, 'store'])->name('resources.store');
    Route::get('{resource}/export.csv', [Admin\ResourceController::class, 'export'])->name('resources.export');
    Route::get('{resource}/{id}', [Admin\ResourceController::class, 'edit'])->whereNumber('id')->name('resources.edit');
    Route::post('{resource}/{id}', [Admin\ResourceController::class, 'update'])->whereNumber('id')->name('resources.update');
    Route::delete('{resource}/{id}', [Admin\ResourceController::class, 'destroy'])->whereNumber('id')->name('resources.destroy');
    Route::post('{resource}/{id}/publication', [Admin\ResourceController::class, 'toggle'])->whereNumber('id')->name('resources.toggle');
});

// Generic CMS pages (/entreprise, /mentions-legales…) — must stay last.
Route::get('{slug}', [Site\PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('page');
