<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            ['room_number' => 'R101', 'capacity' => 1, 'monthly_rent' => 3500],
            ['room_number' => 'R102', 'capacity' => 1, 'monthly_rent' => 3500],
            ['room_number' => 'R103', 'capacity' => 2, 'monthly_rent' => 2800],
            ['room_number' => 'R104', 'capacity' => 2, 'monthly_rent' => 2800],
            ['room_number' => 'R201', 'capacity' => 1, 'monthly_rent' => 3800],
            ['room_number' => 'R202', 'capacity' => 1, 'monthly_rent' => 3800],
            ['room_number' => 'R203', 'capacity' => 2, 'monthly_rent' => 3000],
            ['room_number' => 'R204', 'capacity' => 1, 'monthly_rent' => 3800],
        ];

        foreach ($rooms as $room) {
            Room::query()->firstOrCreate(
                ['room_number' => $room['room_number']],
                [
                    'capacity' => $room['capacity'],
                    'monthly_rent' => $room['monthly_rent'],
                    'status' => 'available',
                ],
            );
        }
    }
}
