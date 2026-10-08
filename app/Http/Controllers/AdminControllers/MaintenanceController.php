<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class MaintenanceController extends Controller
{
    public function index(): View
    {
        $maintenanceRequests = [
            [
                'id' => 'MR-1048',
                'title' => 'Aircon not cooling',
                'category' => 'Appliance',
                'priority' => 'High',
                'priority_class' => 'bg-danger/10 text-danger',
                'room' => 'R201',
                'tenant' => 'Dianne Aquino',
                'reported_at' => '2026-08-27',
                'reported_at_label' => 'Aug 27, 2026',
                'description' => 'The unit turns on, but the airflow is weak and not cold. It may need a refrigerant refill or cleaning.',
                'status' => 'Pending',
            ],
            [
                'id' => 'MR-1047',
                'title' => 'Leaking faucet in shared bathroom',
                'category' => 'Plumbing',
                'priority' => 'Medium',
                'priority_class' => 'bg-copper/15 text-copperDeep',
                'room' => 'R104',
                'tenant' => 'Carlo Mendoza',
                'reported_at' => '2026-08-25',
                'reported_at_label' => 'Aug 25, 2026',
                'description' => 'Water keeps dripping from the sink faucet even when fully closed. The leak has gotten worse over the past 3 days.',
                'status' => 'In Progress',
            ],
            [
                'id' => 'MR-1032',
                'title' => 'Loose door hinge',
                'category' => 'Carpentry',
                'priority' => 'Low',
                'priority_class' => 'bg-paper2 text-inkSoft',
                'room' => 'R103',
                'tenant' => 'Angeline Reyes',
                'reported_at' => '2026-08-10',
                'reported_at_label' => 'Aug 10, 2026',
                'description' => 'The room door sags and scrapes the floor when opening.',
                'status' => 'Resolved',
            ],
        ];

        $statuses = ['Pending', 'In Progress', 'Resolved'];
        $priorities = ['High', 'Medium', 'Low'];
        $summary = [
            'total' => count($maintenanceRequests),
            'pending' => count(array_filter($maintenanceRequests, fn (array $request): bool => $request['status'] === 'Pending')),
            'in_progress' => count(array_filter($maintenanceRequests, fn (array $request): bool => $request['status'] === 'In Progress')),
            'resolved' => count(array_filter($maintenanceRequests, fn (array $request): bool => $request['status'] === 'Resolved')),
        ];

        return view('admin.maintenance.index', compact('maintenanceRequests', 'priorities', 'statuses', 'summary'));
    }
}
