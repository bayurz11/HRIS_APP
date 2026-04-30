<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders authentication screens in indonesian by default', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Masuk ke akun Anda')
        ->assertSee('Alamat email');
});

it('allows guests to switch the locale to english', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Log in')
        ->assertSee('Enterprise HRIS blueprint built as an operational foundation.');
});
