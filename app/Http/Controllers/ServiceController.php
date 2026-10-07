<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Service::class);

        return view('services.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Service::class);

        return view('services.form', ['service' => new Service(['active' => true, 'default_duration' => 60]), 'categories' => ServiceCategory::orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        if ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        } if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }$service = Service::create($data);

        return redirect()->route('services.show', $service)->with('success', 'La fiche a été créée.');
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return view('services.form', ['service' => $service, 'categories' => ServiceCategory::orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        if ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        } if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }$service->update($data);

        return redirect()->route('services.show', $service)->with('success', 'Les modifications ont été enregistrées.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);
        $service->delete();

        return redirect()->route('services.index')->with('success', 'La fiche a été archivée.');
    }

    public function show(Service $service): View
    {
        Gate::authorize('view', $service);
        $service->load(['category', 'variants']);

        return view('services.show', compact('service'));
    }
}
