<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Global search across the entities the user may see. */
class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = mb_substr(trim((string) $request->query('q')), 0, 100);
        $user = $request->user();
        $results = [];
        if (mb_strlen($q) < 2) {
            return response()->json($results);
        }
        $like = "%$q%";
        if ($user->canDo('inquiries.view')) {
            foreach (Inquiry::where(fn ($w) => $w->whereLike('reference', $like)->orWhereLike('email', $like)->orWhereLike('last_name', $like)->orWhereLike('organization', $like)->orWhereLike('subject', $like))->limit(5)->get() as $i) {
                $results[] = ['kind' => 'Demande', 'title' => (string) $i, 'subtitle' => $i->subject, 'url' => route('admin.inquiries.show', $i), 'icon' => 'inbox'];
            }
        }
        if ($user->canDo('crm.view')) {
            foreach (Lead::where(fn ($w) => $w->whereLike('email', $like)->orWhereLike('first_name', $like)->orWhereLike('last_name', $like)->orWhereLike('organization', $like))->limit(5)->get() as $l) {
                $results[] = ['kind' => 'Prospect', 'title' => (string) $l, 'subtitle' => $l->status->label(), 'url' => route('admin.leads.show', $l), 'icon' => 'contact'];
            }
        }
        if ($user->canDo('blog.view')) {
            foreach (BlogArticle::whereLike('title', $like)->limit(5)->get() as $a) {
                $results[] = ['kind' => 'Article', 'title' => $a->title, 'subtitle' => $a->displayStatus(), 'url' => route('admin.resources.edit', ['articles', $a->id]), 'icon' => 'newspaper'];
            }
        }
        if ($user->canDo('catalog.view')) {
            foreach (Product::whereLike('name', $like)->limit(5)->get() as $p) {
                $results[] = ['kind' => 'Solution', 'title' => $p->name, 'subtitle' => $p->status->label(), 'url' => route('admin.resources.edit', ['solutions', $p->id]), 'icon' => 'package'];
            }
        }
        if ($user->canDo('content.view')) {
            foreach (Page::whereLike('title', $like)->limit(5)->get() as $p) {
                $results[] = ['kind' => 'Page', 'title' => $p->title, 'subtitle' => $p->status->label(), 'url' => route('admin.resources.edit', ['pages', $p->id]), 'icon' => 'panels-top-left'];
            }
        }

        return response()->json($results);
    }
}
