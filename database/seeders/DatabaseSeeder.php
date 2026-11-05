<?php

namespace Database\Seeders;

use App\Models\AbsenceRequest;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\ElectricityBill;
use App\Models\MaintenanceRequest;
use App\Models\MeterAssignment;
use App\Models\MeterReading;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Submeter;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ElectricityBillingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@dora.test';

    private const ADMIN_PASSWORD = 'admin123';

    private const DEMO_TENANTS = [
        [
            'name' => 'Alex Rivera',
            'email' => 'alex.rivera@dora.test',
            'password' => 'tenant123',
            'phone' => '555-0101',
            'payment_method' => 'gcash',
        ],
        [
            'name' => 'Jordan Lee',
            'email' => 'jordan.lee@dora.test',
            'password' => 'tenant123',
            'phone' => '555-0102',
            'payment_method' => 'cash',
        ],
    ];

    /**
     * Seed development data only. Admin login: admin@dora.test / admin123.
     */
    public function run(ElectricityBillingService $billingService): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DORA demo data is only seeded in local and testing environments.');

            return;
        }

        $admin = $this->seedDemoUser(
            'System Administrator',
            self::ADMIN_EMAIL,
            self::ADMIN_PASSWORD,
            'admin',
        );

        $this->call(RoomSeeder::class);

        $periodStart = CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();
        $moveInDate = $periodStart->subDays(30)->toDateString();
        $rooms = Room::query()->orderBy('room_number')->get();
        $tenantAssignments = [];

        foreach (self::DEMO_TENANTS as $index => $tenantDetails) {
            $application = $this->seedApplication(
                $tenantDetails['name'],
                $tenantDetails['email'],
                $tenantDetails['phone'],
                $moveInDate,
                'approved',
                $admin,
            );
            $tenant = $this->seedDemoUser(
                $tenantDetails['name'],
                $tenantDetails['email'],
                $tenantDetails['password'],
                'tenant',
            );

            if ($application->status !== 'approved') {
                $this->command?->warn("Skipping room assignment for {$tenant->email}: its application is not approved.");

                continue;
            }

            $assignment = $this->assignApprovedTenant($tenant, $application, $rooms);

            if ($assignment === null) {
                continue;
            }

            $tenantAssignments[$index] = $assignment;
            $this->seedRentCharge($assignment, $admin, $periodStart);
            $this->seedPayment($assignment, $admin, $periodStart, $tenantDetails['payment_method'], $index);

            if ($index === 0) {
                $this->seedAbsenceRequests($assignment, $admin, $periodStart);
            }

            $this->seedElectricityBill(
                $assignment->room,
                $admin,
                $periodStart,
                $periodEnd,
                $billingService,
            );
        }

        $this->seedApplication(
            'Taylor Sample',
            'taylor.sample@dora.test',
            '555-0103',
            CarbonImmutable::today()->addDays(14)->toDateString(),
            'pending',
            $admin,
            $rooms->first()?->id,
        );
        $this->seedApplication(
            'Casey Example',
            'casey.example@dora.test',
            '555-0104',
            CarbonImmutable::today()->addDays(21)->toDateString(),
            'rejected',
            $admin,
            $rooms->first()?->id,
        );

        $this->seedMaintenanceRequests($tenantAssignments);
        $this->seedAnnouncements();
    }

    private function seedDemoUser(string $name, string $email, string $password, string $role): User
    {
        $existingUser = User::withTrashed()->where('email', $email)->first();

        if ($existingUser !== null) {
            if ($existingUser->trashed() || $existingUser->role !== $role) {
                throw new RuntimeException("Cannot seed {$email}: an incompatible user account already exists.");
            }

            return $existingUser;
        }

        $factory = $role === 'admin' ? User::factory()->admin() : User::factory()->tenant();

        return $factory->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }

    private function seedApplication(
        string $name,
        string $email,
        string $phone,
        string $moveInDate,
        string $status,
        User $admin,
        ?int $preferredRoomId = null,
    ): Application {
        $existingApplication = Application::query()->where('email', $email)->first();

        if ($existingApplication !== null) {
            return $existingApplication;
        }

        $application = new Application([
            'applicant_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'preferred_room_id' => $preferredRoomId,
            'move_in_date' => $moveInDate,
        ]);
        $application->forceFill([
            'status' => $status,
            'reviewed_by' => $status === 'pending' ? null : $admin->id,
            'reviewed_at' => $status === 'pending' ? null : now(),
            'admin_remarks' => $status === 'rejected' ? 'Demo application for development.' : null,
        ])->save();

        return $application;
    }

    /**
     * @param  Collection<int, Room>  $rooms
     */
    private function assignApprovedTenant(User $tenant, Application $application, Collection $rooms): ?RoomAssignment
    {
        if ($application->user_id !== null && $application->user_id !== $tenant->id) {
            $this->command?->warn("Skipping {$tenant->email}: its application is linked to another account.");

            return null;
        }

        $existingAssignment = RoomAssignment::query()
            ->where('user_id', $tenant->id)
            ->whereIn('status', ['reserved', 'occupied'])
            ->orderByDesc('start_date')
            ->first();

        if ($existingAssignment !== null) {
            return $existingAssignment;
        }

        $availableRoom = $rooms->first(function (Room $room): bool {
            $activeAssignments = RoomAssignment::query()
                ->where('room_id', $room->id)
                ->whereIn('status', ['reserved', 'occupied'])
                ->count();

            return $activeAssignments < $room->capacity;
        });

        if ($availableRoom === null) {
            $this->command?->warn("No room capacity is available for {$tenant->email}; no assignment was created.");

            return null;
        }

        $application->forceFill([
            'user_id' => $tenant->id,
            'preferred_room_id' => $availableRoom->id,
        ])->save();

        $assignment = RoomAssignment::query()->create([
            'user_id' => $tenant->id,
            'room_id' => $availableRoom->id,
            'application_id' => $application->id,
            'status' => 'occupied',
            'start_date' => $application->move_in_date,
        ]);

        $availableRoom->update(['status' => 'occupied']);

        return $assignment;
    }

    private function seedAbsenceRequests(
        RoomAssignment $assignment,
        User $admin,
        CarbonImmutable $periodStart,
    ): void {
        foreach ([
            ['approved', 4, 'Approved demo absence'],
            ['pending', 10, 'Pending demo absence'],
            ['rejected', 16, 'Rejected demo absence'],
        ] as [$status, $startOffset, $reason]) {
            $startDate = $periodStart->addDays($startOffset)->toDateString();
            $endDate = $periodStart->addDays($startOffset + 1)->toDateString();

            AbsenceRequest::query()->firstOrCreate(
                [
                    'room_assignment_id' => $assignment->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reason' => $reason,
                ],
                [
                    'status' => $status,
                    'reviewed_by' => $status === 'pending' ? null : $admin->id,
                    'reviewed_at' => $status === 'pending' ? null : now(),
                    'admin_remarks' => $status === 'rejected' ? 'Demo request for development.' : null,
                ],
            );
        }
    }

    private function seedRentCharge(
        RoomAssignment $assignment,
        User $admin,
        CarbonImmutable $periodStart,
    ): void {
        $description = 'Monthly rent - '.$periodStart->format('F Y');

        Transaction::query()->firstOrCreate(
            [
                'user_id' => $assignment->user_id,
                'type' => 'charge',
                'description' => $description,
            ],
            [
                'amount' => $assignment->room->monthly_rent,
                'transaction_date' => $periodStart->toDateString(),
                'due_date' => $periodStart->addDays(7)->toDateString(),
                'notes' => 'Demo rent charge; absences do not reduce rent.',
                'recorded_by' => $admin->id,
            ],
        );
    }

    private function seedPayment(
        RoomAssignment $assignment,
        User $admin,
        CarbonImmutable $periodStart,
        string $paymentMethod,
        int $tenantIndex,
    ): void {
        $description = 'Demo rent payment - '.$periodStart->format('F Y');
        $rent = (float) $assignment->room->monthly_rent;

        Transaction::query()->firstOrCreate(
            [
                'user_id' => $assignment->user_id,
                'type' => 'payment',
                'description' => $description,
            ],
            [
                'amount' => number_format($tenantIndex === 0 ? $rent : min($rent, 1000), 2, '.', ''),
                'transaction_date' => $periodStart->addDays(10)->toDateString(),
                'payment_method' => $paymentMethod,
                'notes' => 'Demo payment for rent.',
                'recorded_by' => $admin->id,
            ],
        );
    }

    private function seedElectricityBill(
        Room $room,
        User $admin,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        ElectricityBillingService $billingService,
    ): void {
        $overlappingBillExists = ElectricityBill::query()
            ->where('room_id', $room->id)
            ->whereDate('period_start', '<=', $periodEnd->toDateString())
            ->whereDate('period_end', '>=', $periodStart->toDateString())
            ->exists();

        if ($overlappingBillExists) {
            return;
        }

        $meterAssignments = MeterAssignment::query()
            ->where('room_id', $room->id)
            ->whereDate('installed_at', '<=', $periodEnd->toDateString())
            ->where(function ($query) use ($periodStart): void {
                $query->whereNull('uninstalled_at')
                    ->orWhereDate('uninstalled_at', '>=', $periodStart->toDateString());
            })
            ->with('submeter')
            ->orderBy('installed_at')
            ->orderBy('id')
            ->get();

        if ($meterAssignments->isEmpty()) {
            $activeMeterExists = MeterAssignment::query()
                ->where('room_id', $room->id)
                ->whereNull('uninstalled_at')
                ->exists();

            if ($activeMeterExists) {
                $this->command?->warn("Skipping the demo electricity bill for {$room->room_number}: its active meter does not cover the sample period.");

                return;
            }

            $submeter = Submeter::query()->firstOrCreate(
                ['meter_number' => 'DORA-DEMO-'.$room->room_number],
                [
                    'status' => 'active',
                    'notes' => 'Development sample submeter.',
                ],
            );

            if ($submeter->status !== 'active') {
                $this->command?->warn("Skipping the demo electricity bill for {$room->room_number}: its submeter is not active.");

                return;
            }

            $meterAssignments = collect([
                MeterAssignment::query()->create([
                    'submeter_id' => $submeter->id,
                    'room_id' => $room->id,
                    'installed_by' => $admin->id,
                    'installed_at' => $periodStart->toDateString(),
                ])->load('submeter'),
            ]);
        }

        if ($meterAssignments->count() !== 1) {
            $this->command?->warn("Skipping the demo electricity bill for {$room->room_number}: multiple meter segments need existing readings.");

            return;
        }

        $meterAssignment = $meterAssignments->first();

        if (
            $meterAssignment->installed_at->greaterThan($periodStart)
            || ($meterAssignment->uninstalled_at !== null && $meterAssignment->uninstalled_at->lessThan($periodEnd))
            || $meterAssignment->submeter?->status !== 'active'
        ) {
            $this->command?->warn("Skipping the demo electricity bill for {$room->room_number}: its meter does not cover the full billing period.");

            return;
        }

        if (! $this->seedMeterReadings($meterAssignment, $admin, $periodStart, $periodEnd)) {
            return;
        }

        $billingService->finalize(
            $room,
            $periodStart,
            $periodEnd,
            '15.2500',
            null,
            $admin,
            hash('sha256', sprintf(
                'dora-demo:%d:%s:%s',
                $room->id,
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            )),
        );
    }

    private function seedMeterReadings(
        MeterAssignment $assignment,
        User $admin,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): bool {
        $openingReading = MeterReading::query()
            ->where('meter_assignment_id', $assignment->id)
            ->whereDate('reading_date', $periodStart->toDateString())
            ->first();
        $closingReading = MeterReading::query()
            ->where('meter_assignment_id', $assignment->id)
            ->whereDate('reading_date', $periodEnd->toDateString())
            ->first();

        if ($openingReading === null) {
            $latestReading = MeterReading::query()
                ->where('meter_assignment_id', $assignment->id)
                ->latest('reading_date')
                ->latest('id')
                ->first();

            if ($latestReading !== null && $latestReading->reading_date->greaterThanOrEqualTo($periodStart)) {
                $this->command?->warn('Skipping demo meter readings because a later reading already exists.');

                return false;
            }

            $openingValue = $latestReading === null ? 100.0 : (float) $latestReading->reading_value + 100;
            $openingReading = MeterReading::query()->create([
                'meter_assignment_id' => $assignment->id,
                'reading_value' => number_format($openingValue, 3, '.', ''),
                'reading_date' => $periodStart->toDateString(),
                'recorder_by' => $admin->id,
            ]);
        }

        if ($closingReading === null) {
            $latestReading = MeterReading::query()
                ->where('meter_assignment_id', $assignment->id)
                ->latest('reading_date')
                ->latest('id')
                ->first();

            if ($latestReading !== null && $latestReading->reading_date->greaterThanOrEqualTo($periodEnd)) {
                $this->command?->warn('Skipping the demo electricity bill because a later meter reading already exists.');

                return false;
            }

            $closingValue = (float) $latestReading->reading_value + 120;
            $closingReading = MeterReading::query()->create([
                'meter_assignment_id' => $assignment->id,
                'reading_value' => number_format($closingValue, 3, '.', ''),
                'reading_date' => $periodEnd->toDateString(),
                'recorder_by' => $admin->id,
            ]);
        }

        if ((float) $closingReading->reading_value < (float) $openingReading->reading_value) {
            $this->command?->warn('Skipping the demo electricity bill because its closing reading is below its opening reading.');

            return false;
        }

        return true;
    }

    /**
     * @param  array<int, RoomAssignment>  $tenantAssignments
     */
    private function seedMaintenanceRequests(array $tenantAssignments): void
    {
        $examples = [
            [0, 'Leaking bathroom tap', 'Plumbing', 'Medium', 'Pending'],
            [1, 'Desk lamp outlet check', 'Electrical', 'Low', 'In Progress'],
        ];

        foreach ($examples as [$tenantIndex, $title, $category, $priority, $status]) {
            $assignment = $tenantAssignments[$tenantIndex] ?? null;

            if ($assignment === null) {
                continue;
            }

            MaintenanceRequest::query()->firstOrCreate(
                [
                    'user_id' => $assignment->user_id,
                    'title' => $title,
                ],
                [
                    'room_assignment_id' => $assignment->id,
                    'category' => $category,
                    'priority' => $priority,
                    'description' => 'Development sample maintenance request.',
                    'status' => $status,
                ],
            );
        }
    }

    private function seedAnnouncements(): void
    {
        Announcement::query()->firstOrCreate(
            ['title' => 'Scheduled water tank cleaning'],
            [
                'category' => 'Maintenance',
                'body' => 'Water service may be interrupted during the scheduled cleaning.',
                'author_name' => 'DORA Administration',
                'is_pinned' => true,
                'publish_at' => now()->subDay(),
                'ends_at' => today()->addDays(14)->toDateString(),
            ],
        );

        Announcement::query()->firstOrCreate(
            ['title' => 'Community study evening'],
            [
                'category' => 'Community event',
                'body' => 'Residents are invited to the shared study area for a quiet study evening.',
                'author_name' => 'DORA Administration',
                'is_pinned' => false,
                'publish_at' => now()->addDay(),
                'ends_at' => today()->addDays(8)->toDateString(),
            ],
        );
    }
}
