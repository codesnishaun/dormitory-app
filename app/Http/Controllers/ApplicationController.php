<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $roomOptions = Room::query()
            ->orderBy('room_number')
            ->pluck('room_number', 'id')
            ->map(fn (string $roomNumber): string => "Room {$roomNumber}")
            ->prepend('No preference', '');

        return view('application-form', compact('roomOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'applicant_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'preferred_room_id' => 'nullable|exists:rooms,id',
            'move_in_date' => 'required|date|after_or_equal:today',
            'message' => 'nullable|string|max:1000',
        ]);

        Application::create($validated);

        return redirect()->route('application_form')->with('applied', true);
    }

    
}
