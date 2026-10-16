<?php

namespace App\Http\Controllers\AdminControllers;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $pendingApplications = Application::query()
            ->where('status', 'pending')
            ->with('preferredRoom')
            ->latest()
            ->get();

        $reviewedApplications = Application::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->with('preferredRoom')
            ->orderByDesc('reviewed_at')
            ->take(5)
            ->get();
            
        return view('admin.tenant.index', compact('pendingApplications', 'reviewedApplications'));
    }
}