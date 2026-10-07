<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ServiceCategory::class);

        return view('categories.index', ['categories' => ServiceCategory::withCount('services')->orderBy('sort_order')->orderBy('id')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', ServiceCategory::class);

        return view('categories.form', ['category' => new ServiceCategory(['active' => true, 'sort_order' => 0])]);
    }

    public function store(ServiceCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = ServiceCategory::create($data);

        return redirect()->route('categories.index')->with('success', 'La fiche a été créée.');
    }

    public function edit(ServiceCategory $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.form', ['category' => $category]);
    }

    public function update(ServiceCategoryRequest $request, ServiceCategory $category): RedirectResponse
    {
        $data = $request->validated();
        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Les modifications ont été enregistrées.');
    }

    public function destroy(ServiceCategory $category): RedirectResponse
    {
        Gate::authorize('delete', $category);
        if ($category->services()->exists()) {
            return back()->withErrors(['category' => 'Déplacez ou archivez les prestations de cette catégorie avant de l’archiver.']);
        } $category->delete();

        return redirect()->route('categories.index')->with('success', 'La fiche a été archivée.');
    }
}
