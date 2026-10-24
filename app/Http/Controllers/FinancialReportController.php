<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialReportRequest;
use App\Services\FinancialReport;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function index(FinancialReportRequest $request, FinancialReport $report): View
    {
        $filters = $request->validated();
        $data = DB::transaction(fn (): array => $report->data($filters));

        return view('financial-reports.index', ['filters' => $filters, ...$data]);
    }
}
