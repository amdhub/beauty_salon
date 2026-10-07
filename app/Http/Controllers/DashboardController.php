<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceVariant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $canSeeCustomers = auth()->user()->can('viewAny', Customer::class);
        $canSeeEmployees = auth()->user()->can('viewAny', Employee::class);
        $canSeeCatalog = auth()->user()->can('viewAny', Service::class);

        return view('dashboard', [
            'customerCount' => $canSeeCustomers ? Customer::count() : null,
            'employeeCount' => $canSeeEmployees ? Employee::where('active', true)->count() : null,
            'serviceCount' => $canSeeCatalog ? Service::where('active', true)->whereHas('category', fn ($q) => $q->where('active', true)->whereNull('deleted_at'))->count() : null,
            'variantCount' => $canSeeCatalog ? ServiceVariant::where('active', true)->whereHas('service', fn ($q) => $q->where('active', true)->whereNull('deleted_at')->whereHas('category', fn ($category) => $category->where('active', true)->whereNull('deleted_at')))->count() : null,
            'categories' => $canSeeCatalog ? ServiceCategory::withCount('services')->orderBy('sort_order')->orderBy('id')->get() : collect(),
            'recentCustomers' => $canSeeCustomers ? Customer::latest('id')->limit(5)->get() : collect(),
            'employees' => $canSeeEmployees ? Employee::with('specialties')->where('active', true)->orderBy('name')->limit(4)->get() : collect(),
        ]);
    }
}
