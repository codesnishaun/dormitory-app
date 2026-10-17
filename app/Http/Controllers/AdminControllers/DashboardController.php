<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $vacantRooms = Room::where('status', 'available')->count();
        $tenantCount = User::where('role', 'tenant')->count();

        return view('admin.dashboard', compact('totalRooms', 'occupiedRooms', 'vacantRooms', 'tenantCount'));
    }
}