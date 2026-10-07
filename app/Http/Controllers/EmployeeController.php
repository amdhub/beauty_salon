<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Employee::class);

        return view('employees.index', ['employees' => Employee::with('specialties')->orderBy('name')->orderBy('id')->paginate(12)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Employee::class);

        return view('employees.form', ['employee' => new Employee(['active' => true]), 'categories' => ServiceCategory::orderBy('name')->get(), 'users' => User::orderBy('name')->get()]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('specialties');
        DB::transaction(function () use ($data, $request): void {
            $employee = Employee::create($data);
            $employee->specialties()->sync($request->validated('specialties', []));
        });

        return redirect()->route('employees.index')->with('success', 'La fiche a été créée.');
    }

    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);
        $employee->load('specialties');

        return view('employees.form', ['employee' => $employee, 'categories' => ServiceCategory::orderBy('name')->get(), 'users' => User::orderBy('name')->get()]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->safe()->except('specialties');
        DB::transaction(function () use ($data, $request, $employee): void {
            $employee->update($data);
            $employee->specialties()->sync($request->validated('specialties', []));
        });

        return redirect()->route('employees.index')->with('success', 'Les modifications ont été enregistrées.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);
        $employee->delete();

        return redirect()->route('employees.index')->with('success','La fiche a été archivée.');
    }
}
