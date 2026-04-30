<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('HARIS');
});
