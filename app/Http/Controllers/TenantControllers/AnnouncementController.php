<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_at')
            ->get();

        return view('tenant.announcements', compact('announcements'));
    }
}