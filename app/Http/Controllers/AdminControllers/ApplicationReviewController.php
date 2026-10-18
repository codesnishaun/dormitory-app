<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\RoomAssignment;
use App\Models\TenantAccountInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function destroy(Application $application): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($application): bool {
            $lockedApplication = Application::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($lockedApplication->status !== 'approved' || $lockedApplication->user_id === null) {
                return false;
            }

            $hasCompletedSetup = TenantAccountInvitation::query()
                ->where('user_id', $lockedApplication->user_id)
                ->whereNotNull('used_at')
                ->exists();

            if (! $hasCompletedSetup) {
                return false;
            }

            RoomAssignment::query()
                ->where('application_id', $lockedApplication->id)
                ->update(['application_id' => null]);

            $lockedApplication->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('admin.tenant.index')
                ->with('error', 'Only approved applications whose tenant has completed account setup can be removed.');
        }

        return redirect()
            ->route('admin.tenant.index')
            ->with('success', 'Reviewed application removed.');
    }
}
