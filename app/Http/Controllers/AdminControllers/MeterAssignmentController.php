<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\ElectricityBill;
use App\Models\MeterAssignment;
use App\Models\MeterReading;
use App\Models\Room;
use App\Models\Submeter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeterAssignmentController extends Controller
{
    public function index(): View
    {
        $assignments = MeterAssignment::query()
            ->with(['room', 'submeter', 'installedBy', 'meterReadings.recorder'])
            ->orderByRaw('uninstalled_at IS NULL DESC')
            ->orderByDesc('installed_at')
            ->orderByDesc('id')
            ->paginate(15);

        $rooms = Room::query()->orderBy('room_number')->get();
        $submeters = Submeter::query()
            ->where('status', 'active')
            ->whereDoesntHave('currentAssignment')
            ->orderBy('meter_number')
            ->get();

        return view('admin.meters.assignments', compact('assignments', 'rooms', 'submeters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'submeter_id' => ['required', 'integer', 'exists:submeters,id'],
            'installed_at' => ['required', 'date', 'before_or_equal:today'],
            'initial_reading' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);
            $submeter = Submeter::query()->lockForUpdate()->findOrFail($data['submeter_id']);

            if ($submeter->status !== 'active') {
                throw ValidationException::withMessages([
                    'submeter_id' => 'Only active submeters can be assigned to a room.',
                ]);
            }

            if ($room->currentMeterAssignment()->exists()) {
                throw ValidationException::withMessages([
                    'room_id' => 'This room already has an active meter assignment.',
                ]);
            }

            if ($submeter->currentAssignment()->exists()) {
                throw ValidationException::withMessages([
                    'submeter_id' => 'This submeter is already assigned to a room.',
                ]);
            }

            $installedAt = CarbonImmutable::parse($data['installed_at']);
            $previousRoomAssignment = $room->meterAssignments()
                ->whereNotNull('uninstalled_at')
                ->orderByDesc('uninstalled_at')
                ->first();

            if (
                $previousRoomAssignment !== null
                && $installedAt->lessThanOrEqualTo(CarbonImmutable::parse($previousRoomAssignment->uninstalled_at))
            ) {
                throw ValidationException::withMessages([
                    'installed_at' => 'A replacement meter must be installed after the previous meter was removed.',
                ]);
            }

            if (ElectricityBill::query()
                ->where('room_id', $room->id)
                ->whereDate('period_end', '>=', $installedAt->toDateString())
                ->exists()) {
                throw ValidationException::withMessages([
                    'installed_at' => 'The installation date cannot change a period covered by a finalized bill.',
                ]);
            }

            $assignment = MeterAssignment::query()->create([
                'room_id' => $room->id,
                'submeter_id' => $submeter->id,
                'installed_by' => $request->user()->id,
                'installed_at' => $installedAt->toDateString(),
            ]);

            MeterReading::query()->create([
                'meter_assignment_id' => $assignment->id,
                'reading_value' => $data['initial_reading'],
                'reading_date' => $installedAt->toDateString(),
                'recorder_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('admin.meter-assignments.index')
            ->with('success', 'Meter assigned and initial reading recorded.');
    }

    public function close(Request $request, MeterAssignment $meterAssignment): RedirectResponse
    {
        $data = $request->validate([
            'uninstalled_at' => ['required', 'date', 'before_or_equal:today'],
            'final_reading' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
        ]);

        DB::transaction(function () use ($data, $meterAssignment, $request): void {
            $assignment = MeterAssignment::query()
                ->with('submeter')
                ->lockForUpdate()
                ->findOrFail($meterAssignment->id);
            Room::query()->lockForUpdate()->findOrFail($assignment->room_id);

            if ($assignment->uninstalled_at !== null) {
                throw ValidationException::withMessages([
                    'uninstalled_at' => 'This meter assignment has already been closed.',
                ]);
            }

            if ($assignment->submeter?->status !== 'active') {
                throw ValidationException::withMessages([
                    'uninstalled_at' => 'Only active submeters can receive a final reading.',
                ]);
            }

            $uninstalledAt = CarbonImmutable::parse($data['uninstalled_at']);

            if ($uninstalledAt->lessThanOrEqualTo(CarbonImmutable::parse($assignment->installed_at))) {
                throw ValidationException::withMessages([
                    'uninstalled_at' => 'A meter must be installed for at least one day before it can be removed.',
                ]);
            }

            if ($assignment->meterReadings()
                ->whereDate('reading_date', '>', $uninstalledAt->toDateString())
                ->exists()) {
                throw ValidationException::withMessages([
                    'uninstalled_at' => 'The removal date cannot be earlier than an existing meter reading.',
                ]);
            }

            if ($assignment->electricityBillSegments()
                ->whereDate('segment_end', '>=', $uninstalledAt->toDateString())
                ->exists()) {
                throw ValidationException::withMessages([
                    'uninstalled_at' => 'The removal date cannot change a finalized electricity bill.',
                ]);
            }

            $sameDayReading = $assignment->meterReadings()
                ->whereDate('reading_date', $uninstalledAt->toDateString())
                ->first();

            if ($sameDayReading !== null) {
                if ((float) $sameDayReading->reading_value !== (float) $data['final_reading']) {
                    throw ValidationException::withMessages([
                        'final_reading' => 'A reading already exists for this date with a different value.',
                    ]);
                }
            } else {
                MeterReading::query()->create([
                    'meter_assignment_id' => $assignment->id,
                    'reading_value' => $data['final_reading'],
                    'reading_date' => $uninstalledAt->toDateString(),
                    'recorder_by' => $request->user()->id,
                ]);
            }

            $assignment->update(['uninstalled_at' => $uninstalledAt->toDateString()]);
        });

        return redirect()->route('admin.meter-assignments.index')
            ->with('success', 'Meter assignment closed with its final reading.');
    }
}
