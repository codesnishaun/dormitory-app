<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Submeter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubmeterController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $submeters = Submeter::query()
            ->with([
                'currentAssignment.room',
                'currentAssignment.installedBy',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('meter_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('currentAssignment.room', function ($roomQuery) use ($search) {
                            $roomQuery->where('room_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('meter_number')
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'total' => Submeter::count(),
            'active' => Submeter::where('status', 'active')->count(),
            'inactive' => Submeter::where('status', 'inactive')->count(),
            'retired' => Submeter::where('status', 'retired')->count(),
        ];

        return view('admin.submeter.index', compact('submeters', 'summary', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'meter_number' => ['required', 'string', 'max:50', 'unique:submeters,meter_number'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $submeter = Submeter::create([
            'meter_number' => strtoupper(trim((string) $data['meter_number'])),
            'status' => $data['status'] ?? 'active',
            'notes' => filled($data['notes']) ? trim((string) $data['notes']) : null,
        ]);

        return redirect()->route('admin.submeter.index')
            ->with('success', 'Submeter '.$submeter->meter_number.' was added.');
    }

    public function update(Request $request, Submeter $submeter): RedirectResponse
    {
        $data = $request->validateWithBag('editSubmeter', [
            'meter_number' => ['required', 'string', 'max:50', Rule::unique('submeters', 'meter_number')->ignore($submeter->id)],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($data, $submeter): RedirectResponse {
            $lockedSubmeter = Submeter::query()->lockForUpdate()->findOrFail($submeter->id);

            if ($lockedSubmeter->status === 'retired') {
                return redirect()->route('admin.submeter.index')
                    ->with('error', 'Retired submeters cannot be edited.');
            }

            $lockedSubmeter->update([
                'meter_number' => strtoupper(trim((string) $data['meter_number'])),
                'status' => $data['status'] ?? $lockedSubmeter->status,
                'notes' => filled($data['notes']) ? trim((string) $data['notes']) : null,
            ]);

            return redirect()->route('admin.submeter.index')
                ->with('success', 'Submeter '.$lockedSubmeter->meter_number.' was updated.');
        });
    }

    public function retire(Submeter $submeter): RedirectResponse
    {
        return DB::transaction(function () use ($submeter): RedirectResponse {
            $lockedSubmeter = Submeter::query()->lockForUpdate()->findOrFail($submeter->id);

            if ($lockedSubmeter->status === 'retired') {
                return redirect()->route('admin.submeter.index')
                    ->with('error', 'Submeter '.$lockedSubmeter->meter_number.' is already retired.');
            }

            if ($lockedSubmeter->currentAssignment()->exists()) {
                return redirect()->route('admin.submeter.index')
                    ->with('error', 'Retire the active room assignment before retiring '.$lockedSubmeter->meter_number.'.');
            }

            $lockedSubmeter->update(['status' => 'retired']);

            return redirect()->route('admin.submeter.index')
                ->with('success', 'Submeter '.$lockedSubmeter->meter_number.' was retired.');
        });
    }
}
