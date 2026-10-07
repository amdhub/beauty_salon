<?php

namespace App\Http\Controllers;

use App\Models\SalonVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SalonVisitController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SalonVisit::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $search = trim($filters['search'] ?? '');
        $numberId = preg_match('/^(?:VIS|REC)-0*(\d+)$/iD', $search, $matches) ? (int) $matches[1] : null;
        $visits = SalonVisit::with('payments')->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('customer_name_snapshot', 'like', '%'.$search.'%')->orWhere('customer_phone_snapshot', 'like', '%'.$search.'%')->when($numberId !== null, fn ($n) => $n->orWhere('id', $numberId))))
            ->when(! empty($filters['date']), fn ($q) => $q->whereDate('created_at', $filters['date']))
            ->latest('id')->paginate(20)->withQueryString();

        return view('visits.index', compact('visits', 'filters'));
    }

    public function show(SalonVisit $visit): View
    {
        Gate::authorize('view', $visit);
        $visit->load(['items', 'payments']);

        return view('visits.show', ['visit' => $visit, 'paymentKey' => (string) Str::uuid()]);
    }

    public function receipt(SalonVisit $visit): View
    {
        Gate::authorize('view', $visit);
        $visit->load(['items', 'payments']);

        return view('visits.receipt', compact('visit'));
    }

    public function thermalReceipt(Request $request, SalonVisit $visit): View
    {
        Gate::authorize('view', $visit);
        $visit->load(['items', 'payments']);

        return view('visits.thermal-receipt', [
            'visit' => $visit,
            'autoPrint' => $request->boolean('print'),
        ]);
    }
}
