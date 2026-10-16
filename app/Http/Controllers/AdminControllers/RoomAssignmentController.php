<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\TenantInvitationService;
use Illuminate\Http\Request;

class RoomAssignmentController extends Controller
{
    public function store(Request $request, Application $application, TenantInvitationService $service)
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
        ]);

        $sent = $service->confirmAssignment(
            $application->id,
            $validated['room_id'],
            $request->user()
        );

        return redirect()
            ->route('admin.tenant.index')
            ->with(
                $sent ? 'success' : 'error',
                $sent
                    ? 'Room confirmed and invitation sent.'
                    : 'Room confirmed, but the email failed. Use "Resend invitation" to retry.'
            );
    }
}
