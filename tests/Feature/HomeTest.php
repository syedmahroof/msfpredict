<?php

use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('advertisement prop is null when no active banner exists', function () {
    Advertisement::factory()->inactive()->create();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('advertisement', null)
        );
});

test('the active home banner is passed to the home page', function () {
    $ad = Advertisement::factory()->create([
        'title' => 'Sponsor',
        'image_path' => 'ads/sponsor.png',
        'link_url' => 'https://example.com',
        'placement' => 'home_hero',
        'is_active' => true,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('advertisement', [
                'image_url' => $ad->image_url,
                'link_url' => $ad->link_url,
                'alt' => $ad->title,
            ])
        );
});

test('the lowest sort order wins when several banners are active', function () {
    Advertisement::factory()->create(['title' => 'Second', 'placement' => 'home_hero', 'is_active' => true, 'sort_order' => 5]);
    Advertisement::factory()->create(['title' => 'First', 'placement' => 'home_hero', 'is_active' => true, 'sort_order' => 1]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('advertisement.alt', 'First')
        );
});
