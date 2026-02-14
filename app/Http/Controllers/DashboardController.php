<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        // Get accounts grouped by type
        $accounts = $budget->accounts()
            ->where('is_closed', false)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'balance' => $account->balance,
                    'cleared_balance' => $account->cleared_balance,
                ];
            })
            ->groupBy('type');

        return Inertia::render('Dashboard', [
            'accounts' => $accounts,
            'budgetName' => $budget->name,
        ]);
    }
}
