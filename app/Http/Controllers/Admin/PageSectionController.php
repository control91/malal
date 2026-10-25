<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;

class PageSectionController extends Controller
{
    public function move(PageSection $section, string $direction): RedirectResponse
    {
        $siblings = $section->page->sections()->get()->values();
        $index = $siblings->search(fn ($s) => $s->is($section));
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap >= 0 && $swap < $siblings->count()) {
            $items = $siblings->all();
            [$items[$index], $items[$swap]] = [$items[$swap], $items[$index]];
            foreach ($items as $position => $item) {
                if ($item->order !== $position * 10) {
                    $item->updateQuietly(['order' => $position * 10]);
                }
            }
            Audit::record(AuditAction::Update, $section->page, ['sections' => 'ordre modifié']);
        }

        return back();
    }
}
