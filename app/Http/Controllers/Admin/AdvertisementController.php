<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvertisementRequest;
use App\Models\Advertisement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdvertisementController extends Controller
{
    public function index(): Response
    {
        $advertisements = Advertisement::orderBy('placement')
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return Inertia::render('Admin/Advertisements/Index', [
            'advertisements' => $advertisements,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Advertisements/Create', [
            'placements' => $this->placements(),
        ]);
    }

    public function store(StoreAdvertisementRequest $request): RedirectResponse
    {
        $advertisement = new Advertisement($request->safe()->except('image'));
        $advertisement->image_path = $request->file('image')->store('ads', 'public');
        $advertisement->save();

        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement created!');
    }

    public function edit(Advertisement $advertisement): Response
    {
        return Inertia::render('Admin/Advertisements/Edit', [
            'advertisement' => $advertisement,
            'placements' => $this->placements(),
        ]);
    }

    public function update(StoreAdvertisementRequest $request, Advertisement $advertisement): RedirectResponse
    {
        $advertisement->fill($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($advertisement->image_path);
            $advertisement->image_path = $request->file('image')->store('ads', 'public');
        }

        $advertisement->save();

        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement updated!');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        Storage::disk('public')->delete($advertisement->image_path);
        $advertisement->delete();

        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement deleted!');
    }

    /**
     * The advertisement slots available on the site.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function placements(): array
    {
        return [
            ['value' => 'home_hero', 'label' => 'Home — below hero banner'],
        ];
    }
}
