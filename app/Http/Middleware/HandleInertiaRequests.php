<?php

namespace App\Http\Middleware;

use App\Admin\Registry;
use App\Models\Inquiry;
use App\Models\NavigationMenu;
use App\Models\SiteSetting;
use App\Support\AntiSpam;
use App\Support\Capabilities;
use App\Support\Images;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** Props shared with every page. Only non-sensitive data reaches the browser. */
    public function share(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $request->is('admin', 'admin/*');

        return [
            ...parent::share($request),
            'ziggy' => fn () => [...(new Ziggy(group: $isAdmin ? null : 'public'))->toArray(), 'location' => $request->url()],
            'site' => fn () => SiteSetting::current()->toPublicArray(),
            'menus' => fn () => $isAdmin ? [] : NavigationMenu::tree(),
            'auth' => fn () => $user ? [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->display_name,
                    'first_name' => $user->first_name,
                    'email' => $user->email,
                    'role' => $user->role->label(),
                    'avatar' => Images::url($user->avatar),
                ],
                'can' => Capabilities::forUser($user),
                'unread' => $user->adminNotifications()->whereNull('read_at')->count(),
            ] : null,
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'adminNav' => fn () => $isAdmin && $user ? Registry::navigation($user) : null,
            'newInquiries' => fn () => $isAdmin && $user?->canDo('inquiries.view') ? Inquiry::where('status', 'new')->count() : 0,
            'antispam' => fn () => $isAdmin ? null : AntiSpam::token(),
            'seo' => fn () => null,
        ];
    }
}
