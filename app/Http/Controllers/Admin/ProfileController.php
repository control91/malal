<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Profile', [
            'profile' => $user->only(['first_name', 'last_name', 'job_title', 'phone', 'bio', 'email']) + ['role' => $user->role->label(), 'avatar' => Images::url($user->avatar)],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:80'], 'last_name' => ['nullable', 'string', 'max:80'], 'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'], 'bio' => ['nullable', 'string', 'max:2000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('malal.max_image_kb')],
        ]);
        $user = $request->user();
        $user->fill(collect($data)->except('avatar')->map(fn ($v) => $v ?? '')->all());
        if ($request->hasFile('avatar')) {
            $user->avatar = Images::store($request->file('avatar'), 'avatars');
        }
        $user->save();

        return back()->with('success', 'Profil mis à jour.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('success', 'Mot de passe modifié.');
    }
}
