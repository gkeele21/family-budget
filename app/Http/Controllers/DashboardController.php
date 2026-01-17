<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\MonthlyBudget;
use App\Models\Transaction;
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
            return redirect()->route('budgets.create');
        }

        $currentMonth = now()->format('Y-m');

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

        // Calculate available to budget
        // Total income for current month minus total budgeted for current month
        $totalIncome = Transaction::where('budget_id', $budget->id)
            ->where('type', 'income')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $totalBudgeted = MonthlyBudget::whereHas('category.group', function ($query) use ($budget) {
            $query->where('budget_id', $budget->id);
        })
            ->where('month', $currentMonth)
            ->sum('budgeted_amount');

        $availableToBudget = $totalIncome - $totalBudgeted;

        return Inertia::render('Dashboard', [
            'accounts' => $accounts,
            'availableToBudget' => (float) $availableToBudget,
            'budgetName' => $budget->name,
        ]);
    }
}
