<?php

$latitude = env('DORMITORY_LATITUDE');
$longitude = env('DORMITORY_LONGITUDE');

return [
    'name' => env('DORMITORY_NAME', 'Cozy Haven Dormitory'),
    'address' => env('DORMITORY_ADDRESS', 'San Ildefonso, Bulacan, Philippines'),
    'latitude' => is_numeric($latitude) && $latitude >= -90 && $latitude <= 90
        ? (float) $latitude
        : null,
    'longitude' => is_numeric($longitude) && $longitude >= -180 && $longitude <= 180
        ? (float) $longitude
        : null,
];
