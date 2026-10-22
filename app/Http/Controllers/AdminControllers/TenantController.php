<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\User;
use App\Services\TenantInvitationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = User::query()
            ->where('role', 'tenant')
            ->with([
                'roomAssignments' => fn ($query) => $query
                    ->whereIn('status', ['reserved', 'occupied'])
                    ->with(['room', 'application'])
                    ->orderByDesc('start_date')
                    ->orderByDesc('id'),
            ])
            ->orderBy('name')
            ->paginate(15);

        $archivedTenants = User::onlyTrashed()
            ->where('role', 'tenant')
            ->orderByDesc('deleted_at')
            ->paginate(15, ['*'], 'archived_page');

        $pendingApplications = Application::query()
            ->where('status', 'pending')
            ->with('preferredRoom')
            ->latest()
            ->get();

        $reviewedApplications = Application::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->with([
                'preferredRoom',
                'user.tenantAccountInvitations' => fn ($query) => $query->whereNotNull('used_at'),
            ])
            ->orderByDesc('reviewed_at')
            ->take(5)
            ->get();

        $rooms = Room::orderBy('room_number')->get();

        return view('admin.tenant.index', compact(
            'tenants',
            'archivedTenants',
            'pendingApplications',
            'reviewedApplications',
            'rooms',
        ));
    }

    public function store(Request $request, TenantInvitationService $service): RedirectResponse
    {
        $validated = $request->validateWithBag('addTenant', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ], [
            'email.unique' => 'An account with this email already exists. Enter a different email.',
        ]);

        $sent = $service->createTenantAndInvite(
            $validated['name'],
            $validated['email'],
            $request->user()
        );

        return redirect()
            ->route('admin.tenant.index')
            ->with(
                $sent ? 'success' : 'error',
                $sent
                    ? 'Tenant account added and setup invitation sent.'
                    : 'Tenant account added, but the setup invitation email could not be sent.'
            );
    }

    public function update(Request $request, User $tenant): RedirectResponse
    {
        abort_unless($tenant->role === 'tenant', 404);

        $validated = $request->validateWithBag('editTenant', [
            'editing_id' => ['required', 'integer', Rule::in([$tenant->id])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($tenant->id)],
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return redirect()
            ->route('admin.tenant.index')
            ->with('success', 'Tenant updated.');
    }

    public function destroy(User $tenant): RedirectResponse
    {
        abort_unless($tenant->role === 'tenant', 404);

        try {
            $archived = DB::transaction(function () use ($tenant): bool {
                $lockedTenant = User::query()
                    ->lockForUpdate()
                    ->findOrFail($tenant->id);

                abort_unless($lockedTenant->role === 'tenant', 404);

                if (RoomAssignment::query()
                    ->where('user_id', $lockedTenant->id)
                    ->whereIn('status', ['reserved', 'occupied'])
                    ->exists()) {
                    return false;
                }

                $lockedTenant->delete();

                return true;
            });
        } catch (QueryException $exception) {
            report($exception);

            return redirect()
                ->route('admin.tenant.index')
                ->with('error', 'This tenant could not be archived because of linked records.');
        }

        if (! $archived) {
            return redirect()
                ->route('admin.tenant.index')
                ->with('error', 'This tenant still has an active or reserved room assignment. End or cancel it before archiving this account.');
        }

        return redirect()
            ->route('admin.tenant.index')
            ->with('success', 'Tenant archived. Room assignment and account history have been preserved.');
    }

    public function restore(int $tenant): RedirectResponse
    {
        $archivedTenant = User::onlyTrashed()
            ->where('role', 'tenant')
            ->findOrFail($tenant);

        $archivedTenant->restore();

        return redirect()
            ->route('admin.tenant.index')
            ->with('success', 'Tenant account restored.');
    }
}
