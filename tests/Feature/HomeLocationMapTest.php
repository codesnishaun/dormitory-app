<?php

test('the home page explains when verified dormitory coordinates are missing', function () {
    config([
        'dormitory.latitude' => null,
        'dormitory.longitude' => null,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('The interactive map is waiting for verified dormitory coordinates.')
        ->assertSee('Dormitory coordinates have not been configured yet.')
        ->assertSee('data-latitude=""', false)
        ->assertSee('data-longitude=""', false);
});

test('the home page keeps manual directions available without verified coordinates', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Open manual directions in Google Maps')
        ->assertSee('destination=Cozy+Haven+Dormitory', false);
});
