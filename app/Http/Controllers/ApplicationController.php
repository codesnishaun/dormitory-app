<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Room;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = Room::all();

    return view('application-form', compact('rooms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
        'applicant_name'    => 'required|string|max:255',
        'phone'             => 'required|string|max:30',
        'email'             => 'required|email|max:255',
        'preferred_room_id' => 'nullable|exists:rooms,id',
        'move_in_date'      => 'required|date|after_or_equal:today',
        'message'           => 'nullable|string|max:1000',
    ]);

    Application::create($validated);

    return redirect()->route('application_form')->with('applied', true);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
