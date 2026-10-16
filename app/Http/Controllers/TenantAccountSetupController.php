<?php

namespace App\Http\Controllers;

use App\Models\TenantAccountInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TenantAccountSetupController extends Controller
{
    public function show(string $token): View
    {
        $invitation = TenantAccountInvitation::with('user')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_unless($this->isUsable($invitation), 404);

        return view('auth.tenant-account-setup', ['token' => $token]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $tokenHash = hash('sha256', $token);

        $activated = DB::transaction(function () use ($tokenHash, $validated) {
            $invitation = TenantAccountInvitation::with('user')
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $this->isUsable($invitation)) {
                return false;
            }

            $invitation->user->forceFill([
                'password' => Hash::make($validated['password']),
            ])->save();

            $invitation->forceFill(['used_at' => now()])->save();

            return true;
        });

        abort_unless($activated, 404);

        return redirect()->route('login')
            ->with('status', 'Your password has been set. You can now log in.');
    }

    private function isUsable(?TenantAccountInvitation $invitation): bool
    {
        return $invitation !== null
            && $invitation->used_at === null
            && $invitation->expires_at->isFuture()
            && $invitation->user !== null;
    }
}