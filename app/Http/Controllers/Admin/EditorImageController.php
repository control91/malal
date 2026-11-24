<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Registry;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Support\Images;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Images inserted inside rich text (project details, articles…). Also listed in the media library. */
class EditorImageController extends Controller
{
    public function store(Request $request, string $resource): JsonResponse
    {
        $res = Registry::find($resource);
        $fields = $res ? array_merge($res::fields(), ...array_map(fn ($repeater) => $repeater->fields, $res::repeaters())) : [];
        abort_unless(collect($fields)->contains(fn ($field) => $field->type === 'richtext'), 404);
        Gate::authorize($res::$area.'.edit');

        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.config('malal.max_image_kb')],
            'alt' => ['nullable', 'string', 'max:200'],
        ]);
        $path = Images::store($request->file('image'), 'editor');
        $name = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME);
        MediaAsset::create([
            'title' => mb_substr($name ?: 'Image', 0, 160), 'file' => $path, 'alt_text' => (string) ($data['alt'] ?? ''),
            'size' => (int) Storage::disk('media')->size($path), 'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['url' => Images::url($path), 'alt' => (string) ($data['alt'] ?? '')]);
    }
}
