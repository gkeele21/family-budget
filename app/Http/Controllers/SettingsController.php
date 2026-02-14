<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        // Count pending invites for this user
        $pendingInviteCount = Invite::where('email', strtolower($user->email))
            ->whereNull('accepted_at')
            ->count();

        // Get counts for settings display
        $accountCount = $budget ? $budget->accounts()->count() : 0;
        $categoryCount = $budget ? \App\Models\Category::whereHas('group', fn($q) => $q->where('budget_id', $budget->id))->count() : 0;
        $payeeCount = $budget ? $budget->payees()->count() : 0;
        $recurringCount = $budget ? $budget->recurringTransactions()->where('is_active', true)->count() : 0;

        return Inertia::render('Settings/Index', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'budgetName' => $budget?->name,
            'pendingInviteCount' => $pendingInviteCount,
            'counts' => [
                'accounts' => $accountCount,
                'categories' => $categoryCount,
                'payees' => $payeeCount,
                'recurring' => $recurringCount,
            ],
        ]);
    }

    public function accounts()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $accounts = $budget->accounts()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'starting_balance' => (float) $account->starting_balance,
                'balance' => $account->balance,
                'is_closed' => $account->is_closed,
            ]);

        return Inertia::render('Settings/Accounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function categories()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $categoryGroups = $budget->categoryGroups()
            ->with(['categories' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'categories' => $group->categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'icon' => $c->icon,
                    'default_amount' => (float) $c->default_amount,
                    'is_hidden' => $c->is_hidden,
                ]),
            ]);

        return Inertia::render('Settings/Categories/Index', [
            'categoryGroups' => $categoryGroups,
        ]);
    }
}
