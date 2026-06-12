<?php

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function adAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('non-admins cannot access advertisement management', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin/advertisements')->assertForbidden();
});

test('admin can view the advertisement pages', function () {
    $admin = adAdmin();
    $ad = Advertisement::factory()->create();

    $this->actingAs($admin)->get('/admin/advertisements')
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Advertisements/Index')
            ->has('advertisements', 1)
        );

    $this->actingAs($admin)->get('/admin/advertisements/create')->assertSuccessful();
    $this->actingAs($admin)->get("/admin/advertisements/{$ad->id}/edit")->assertSuccessful();
});

test('admin can create an advertisement with an uploaded image', function () {
    $admin = adAdmin();

    $this->actingAs($admin)
        ->post('/admin/advertisements', [
            'title' => 'Ramadan Sponsor',
            'image' => UploadedFile::fake()->image('banner.png', 1200, 400),
            'link_url' => 'https://example.com',
            'placement' => 'home_hero',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->assertRedirect(route('admin.advertisements.index'));

    $ad = Advertisement::where('title', 'Ramadan Sponsor')->first();
    expect($ad)->not->toBeNull();
    Storage::disk('public')->assertExists($ad->image_path);
});

test('creating an advertisement requires a title and image', function () {
    $admin = adAdmin();

    $this->actingAs($admin)
        ->post('/admin/advertisements', [
            'title' => '',
            'placement' => 'home_hero',
        ])
        ->assertSessionHasErrors(['title', 'image']);
});

test('admin can update an advertisement and replace its image', function () {
    $admin = adAdmin();
    $ad = Advertisement::factory()->create([
        'title' => 'Old',
        'image_path' => UploadedFile::fake()->image('old.png')->store('ads', 'public'),
        'is_active' => true,
    ]);
    $oldPath = $ad->image_path;

    $this->actingAs($admin)
        ->post("/admin/advertisements/{$ad->id}", [
            '_method' => 'patch',
            'title' => 'New',
            'image' => UploadedFile::fake()->image('new.png'),
            'link_url' => null,
            'placement' => 'home_hero',
            'is_active' => false,
            'sort_order' => 3,
        ])
        ->assertRedirect(route('admin.advertisements.index'));

    $ad->refresh();
    expect($ad->title)->toBe('New')
        ->and($ad->is_active)->toBeFalse()
        ->and($ad->sort_order)->toBe(3)
        ->and($ad->image_path)->not->toBe($oldPath);

    Storage::disk('public')->assertExists($ad->image_path);
    Storage::disk('public')->assertMissing($oldPath);
});

test('updating without a new image keeps the existing one', function () {
    $admin = adAdmin();
    $ad = Advertisement::factory()->create([
        'image_path' => UploadedFile::fake()->image('keep.png')->store('ads', 'public'),
    ]);
    $originalPath = $ad->image_path;

    $this->actingAs($admin)
        ->post("/admin/advertisements/{$ad->id}", [
            '_method' => 'patch',
            'title' => 'Renamed',
            'placement' => 'home_hero',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->assertRedirect(route('admin.advertisements.index'));

    $ad->refresh();
    expect($ad->title)->toBe('Renamed')
        ->and($ad->image_path)->toBe($originalPath);
    Storage::disk('public')->assertExists($originalPath);
});

test('admin can delete an advertisement and its image', function () {
    $admin = adAdmin();
    $ad = Advertisement::factory()->create([
        'image_path' => UploadedFile::fake()->image('gone.png')->store('ads', 'public'),
    ]);
    $path = $ad->image_path;

    $this->actingAs($admin)
        ->delete("/admin/advertisements/{$ad->id}")
        ->assertRedirect(route('admin.advertisements.index'));

    expect(Advertisement::find($ad->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
