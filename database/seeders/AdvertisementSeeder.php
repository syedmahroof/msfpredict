<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AdvertisementSeeder extends Seeder
{
    /**
     * Seed a single sample home-page banner. Idempotent: safe to run repeatedly.
     */
    public function run(): void
    {
        $imagePath = 'ads/sample-banner.png';
        $source = public_path('images/muslim-league-flag.png');

        // Copy the Muslim League flag onto the public disk so the uploaded-image
        // workflow has a real file to serve (no external URL needed).
        if (is_file($source)) {
            Storage::disk('public')->put($imagePath, (string) file_get_contents($source));
        }

        Advertisement::updateOrCreate(
            ['title' => 'Sample Sponsor'],
            [
                'image_path' => $imagePath,
                'link_url' => 'https://example.com',
                'placement' => 'home_hero',
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
    }
}
