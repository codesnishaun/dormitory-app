<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\ElectricityBill;
use App\Models\Room;
use App\Services\ElectricityBillingService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ElectricityBillController extends Controller
{
    public function index(): View
    {
        $rooms = Room::query()->orderBy('room_number')->get();
        $bills = ElectricityBill::query()
            ->with(['room', 'finalizedBy'])
            ->latest('period_end')
            ->latest('id')
            ->paginate(15);

        return view('admin.Billing.electricity', compact('rooms', 'bills'));
    }

    public function preview(Request $request, ElectricityBillingService $billing): View
    {
        $data = $this->validatedCycle($request);
        $room = Room::query()->findOrFail($data['room_id']);
        $preview = $billing->preview(
            $room,
            CarbonImmutable::parse($data['period_start']),
            CarbonImmutable::parse($data['period_end']),
            (string) $data['rate_per_kwh'],
        );
        $token = Str::random(48);
        $request->session()->put('electricity_bill_previews.'.$token, $data);

        return view('admin.Billing.electricity-preview', compact('preview', 'token'));
    }

    public function finalize(Request $request, ElectricityBillingService $billing): RedirectResponse
    {
        $data = $this->validatedCycle($request);
        $validatedToken = $request->validate([
            'preview_token' => ['required', 'string', 'size:48'],
        ])['preview_token'];
        $previewKey = 'electricity_bill_previews.'.$validatedToken;
        $previewData = $request->session()->get($previewKey);

        if ($previewData !== $data) {
            throw ValidationException::withMessages([
                'preview_token' => 'The bill preview has expired or its details changed. Preview the bill again before finalizing.',
            ]);
        }

        $bill = $billing->finalize(
            Room::query()->findOrFail($data['room_id']),
            CarbonImmutable::parse($data['period_start']),
            CarbonImmutable::parse($data['period_end']),
            (string) $data['rate_per_kwh'],
            $request->user(),
            hash('sha256', $request->user()->id.':'.$validatedToken),
        );

        $request->session()->forget($previewKey);

        return redirect()->route('admin.electricity-bills.show', $bill)
            ->with('success', 'Electricity bill finalized and tenant charges recorded.');
    }

    public function show(ElectricityBill $electricityBill): View
    {
        $electricityBill->load([
            'room',
            'finalizedBy',
            'segments.meterAssignment.submeter',
            'allocations.tenant',
            'allocations.transaction',
        ]);

        return view('admin.Billing.electricity-show', ['bill' => $electricityBill]);
    }

    /**
     * @return array{room_id: int, period_start: string, period_end: string, rate_per_kwh: string}
     */
    private function validatedCycle(Request $request): array
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'period_start' => ['required', 'date', 'before_or_equal:period_end'],
            'period_end' => ['required', 'date', 'before_or_equal:today'],
            'rate_per_kwh' => ['required', 'numeric', 'gt:0', 'max:999999.9999', 'decimal:0,4'],
        ]);

        return [
            'room_id' => (int) $data['room_id'],
            'period_start' => CarbonImmutable::parse($data['period_start'])->toDateString(),
            'period_end' => CarbonImmutable::parse($data['period_end'])->toDateString(),
            'rate_per_kwh' => number_format((float) $data['rate_per_kwh'], 4, '.', ''),
        ];
    }
}
