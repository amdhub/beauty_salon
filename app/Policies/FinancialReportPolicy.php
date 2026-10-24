<?php

namespace App\Policies;

use App\Models\User;

class FinancialReportPolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermission('reports.financial');
    }
}
