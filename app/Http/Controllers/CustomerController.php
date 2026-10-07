<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\SalonVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Customer::class);

        return view('customers.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Customer::class);

        return view('customers.form', ['customer' => new Customer([]), 'employees' => Employee::where('active', true)->orderBy('name')->get()]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)->with('success', 'La fiche a été créée.');
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view('customers.form', ['customer' => $customer, 'employees' => Employee::where('active', true)->orderBy('name')->get()]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Les modifications ont été enregistrées.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'La fiche a été archivée.');
    }

    public function show(Customer $customer): View
    {
        Gate::authorize('view', $customer);
        $customer->load('preferredBeautician');

        $visits = auth()->user()->can('viewAny', SalonVisit::class)
            ? $customer->visits()->with('payments')->latest('id')->paginate(10)
            : null;

        return view('customers.show', compact('customer', 'visits'));
    }
}
