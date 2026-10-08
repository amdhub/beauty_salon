<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRepaymentRequest;
use App\Models\StaffDebt;
use App\Services\StaffDebts;
use Illuminate\Http\RedirectResponse;

class StaffRepaymentController extends Controller
{
    public function store(StoreStaffRepaymentRequest $request, StaffDebt $debt, StaffDebts $debts): RedirectResponse
    {
        $debts->repay($debt, $request->user(), $request->validated());

        return redirect()->route('staff-debts.show', $debt)->with('success', 'Le remboursement a été enregistré et le solde actualisé.');
    }
}
