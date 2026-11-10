<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Admin\AnnouncementCreationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public const CATEGORIES = Announcement::CATEGORIES;

    public function index(Request $request)
    {
        $published = Announcement::published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_at')
            ->get();

        $scheduled = Announcement::scheduled()
            ->orderBy('publish_at')
            ->get();

        $editing = $request->filled('edit')
            ? Announcement::find($request->query('edit'))
            : null;

        return view('admin.announcement.index', [
            'publishedAnnouncements' => $published->map->toCard()->all(),
            'scheduledAnnouncements' => $scheduled->map->toCard()->all(),
            'pinnedCount' => $published->where('is_pinned', true)->count(),
            'categories' => self::CATEGORIES,
            'editing' => $editing,
        ]);
    }

    public function store(Request $request, AnnouncementCreationService $announcements)
    {
        $data = $this->validated($request);
        $data['is_pinned'] = $request->boolean('is_pinned');

        $announcements->create(
            $request->user(),
            $data,
        );

        return redirect()->route('admin.announcement.index')
            ->with('success', 'Announcement posted!');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $this->validated($request);

        $announcement->update([
            'title' => $data['title'],
            'category' => $data['category'],
            'body' => $data['body'],
            'is_pinned' => $request->boolean('is_pinned'),
            'publish_at' => $this->resolvePublishAt($data, $announcement),
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return redirect()->route('admin.announcement.index')
            ->with('success', 'Announcement updated!');
    }

    public function togglePin(Announcement $announcement)
    {
        $announcement->update(['is_pinned' => ! $announcement->is_pinned]);

        return redirect()->route('admin.announcement.index')
            ->with('success', $announcement->is_pinned ? 'Announcement pinned.' : 'Announcement unpinned.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcement.index')
            ->with('success', 'Announcement deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', Announcement::CATEGORIES)],
            'body' => ['required', 'string'],
            'publish_mode' => ['required', 'in:now,schedule'],
            'publish_at' => ['required_if:publish_mode,schedule', 'nullable', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
    }

    private function resolvePublishAt(array $data, ?Announcement $existing = null): Carbon
    {
        if ($data['publish_mode'] === 'schedule') {
            return Carbon::parse($data['publish_at']);
        }

        // Kapag in-edit ang published na, huwag galawin ang orihinal na petsa
        if ($existing && $existing->publish_at->isPast()) {
            return $existing->publish_at;
        }

        return now();
    }
}
