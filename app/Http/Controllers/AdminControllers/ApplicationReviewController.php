<?php

namespace App\Http\Controllers\AdminControllers;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ApplicationReviewController extends Controller
{
    public function approve(Application $application)
    {
        $updated = Application::query()
            ->whereKey($application->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_remarks' => null,
            ]);

        if (! $updated) {
            return redirect()
                ->route('admin.tenant.index')
                ->with('error', 'This application has already been reviewed.');
        }

        return redirect()
            ->route('admin.tenant.index')
            ->with('success', 'Application approved successfully.');
    }

    public function reject(Request $request, Application $application)
    {
        $validated = $request->validate([
            'admin_remarks' => 'nullable|string|max:1000',
        ]);

        $updated = Application::query()
            ->whereKey($application->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_remarks' => $validated['admin_remarks'] ?? null,
            ]);

        if (! $updated) {
            return redirect()
                ->route('admin.tenant.index')
                ->with('error', 'This application has already been reviewed.');
        }

        return redirect()
            ->route('admin.tenant.index')
            ->with('success', 'Application rejected successfully.');
    }
}
