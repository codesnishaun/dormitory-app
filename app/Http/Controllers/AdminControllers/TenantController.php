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
        return view('admin.tenant.index', compact('pendingApplications'));
    }
}