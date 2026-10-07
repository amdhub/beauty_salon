<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordPaymentRequest;
use App\Models\SalonVisit;
use App\Services\VisitPayments;
use Illuminate\Http\RedirectResponse;

class VisitPaymentController extends Controller
{
    public function store(RecordPaymentRequest $request, SalonVisit $visit, VisitPayments $payments): RedirectResponse
    {
        $payments->record($visit, $request->user(), $request->validated());

        return redirect()->route('visits.show', $visit)->with('success', 'Paiement enregistré. Le solde a été mis à jour.');
    }
}
