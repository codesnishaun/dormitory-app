<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\MeterAssignment;
use App\Models\MeterReading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeterReadingController extends Controller
{
    public function store(Request $request, MeterAssignment $meterAssignment): RedirectResponse
    {
        $data = $request->validate([
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
            'reading_value' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
        ]);

        DB::transaction(function () use ($data, $meterAssignment, $request): void {
            $assignment = MeterAssignment::query()
                ->with('submeter')
                ->lockForUpdate()
                ->findOrFail($meterAssignment->id);

            if ($assignment->uninstalled_at !== null || $assignment->submeter?->status !== 'active') {
                throw ValidationException::withMessages([
                    'reading_date' => 'New readings can only be recorded for active meter assignments.',
                ]);
            }

            MeterReading::query()->create([
                'meter_assignment_id' => $assignment->id,
                'reading_date' => $data['reading_date'],
                'reading_value' => $data['reading_value'],
                'recorder_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('admin.meter-assignments.index')
            ->with('success', 'Meter reading recorded.');
    }
}
