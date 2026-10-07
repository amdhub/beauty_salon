<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceVariantRequest;
use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceVariantController extends Controller
{
    public function create(Service $service): View
    {
        Gate::authorize('create', ServiceVariant::class);

        return view('variants.form', ['service' => $service, 'variant' => new ServiceVariant(['active' => true, 'sort_order' => 0])]);
    }

    public function store(ServiceVariantRequest $request, Service $service): RedirectResponse
    {
        $service->variants()->create($request->validated());

        return redirect()->route('services.show', $service)->with('success', 'Le tarif a été ajouté.');
    }

    public function edit(Service $service, ServiceVariant $variant): View
    {
        Gate::authorize('update', $variant);

        return view('variants.form', compact('service', 'variant'));
    }

    public function update(ServiceVariantRequest $request, Service $service, ServiceVariant $variant): RedirectResponse
    {
        $variant->update($request->validated());

        return redirect()->route('services.show', $service)->with('success', 'Le tarif a été modifié.');
    }

    public function destroy(Service $service, ServiceVariant $variant): RedirectResponse
    {
        Gate::authorize('delete', $variant);
        $variant->delete();

        return redirect()->route('services.show', $service)->with('success','Le tarif a été archivé.');
    }
}
