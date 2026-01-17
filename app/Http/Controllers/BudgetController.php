<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\MonthlyBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function create()
    {
        return Inertia::render('Budgets/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_monthly_income' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();

        $budget = Budget::create([
            'name' => $validated['name'],
            'owner_id' => $user->id,
            'default_monthly_income' => $validated['default_monthly_income'] ?? null,
        ]);

        // Add user to budget_users as owner
        $budget->users()->attach($user->id, [
            'role' => 'owner',
            'accepted_at' => now(),
        ]);

        // Set as current budget
        $user->update(['current_budget_id' => $budget->id]);

        return redirect()->route('dashboard');
    }

    public function select(Budget $budget)
    {
        $user = Auth::user();

        // Check if user has access to this budget
        if (!$budget->users()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $user->update(['current_budget_id' => $budget->id]);

        return redirect()->route('dashboard');
    }

    public function index(Request $request, ?string $month = null)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $month = $month ?? now()->format('Y-m');

        // Get all category groups with categories and their monthly budget data
        $categoryGroups = $budget->categoryGroups()
            ->with(['categories' => function ($query) use ($month) {
                $query->orderBy('sort_order')
                    ->with(['monthlyBudgets' => function ($q) use ($month) {
                        $q->where('month', $month);
                    }]);
            }])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($group) use ($month) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'categories' => $group->categories->map(function ($category) use ($month) {
                        $budgeted = $category->getBudgetedForMonth($month);
                        $spent = $category->getSpentForMonth($month);
                        $available = $budgeted - $spent;

                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'icon' => $category->icon,
                            'budgeted' => $budgeted,
                            'spent' => $spent,
                            'available' => $available,
                        ];
                    }),
                ];
            });

        // Calculate totals
        $totalBudgeted = $categoryGroups->sum(fn($g) => $g['categories']->sum('budgeted'));
        $totalSpent = $categoryGroups->sum(fn($g) => $g['categories']->sum('spent'));
        $totalAvailable = $totalBudgeted - $totalSpent;

        // Calculate "To Budget" (income - budgeted)
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $totalIncome = $budget->transactions()
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $toBudget = $totalIncome - $totalBudgeted;

        return Inertia::render('Budget/Index', [
            'month' => $month,
            'categoryGroups' => $categoryGroups,
            'summary' => [
                'toBudget' => (float) $toBudget,
                'budgeted' => (float) $totalBudgeted,
                'spent' => (float) $totalSpent,
                'available' => (float) $totalAvailable,
            ],
        ]);
    }

    public function update(Request $request, string $month)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        $validated = $request->validate([
            'budgets' => 'required|array',
            'budgets.*.category_id' => 'required|exists:categories,id',
            'budgets.*.amount' => 'required|numeric|min:0',
        ]);

        foreach ($validated['budgets'] as $item) {
            MonthlyBudget::updateOrCreate(
                [
                    'category_id' => $item['category_id'],
                    'month' => $month,
                ],
                [
                    'budgeted_amount' => $item['amount'],
                ]
            );
        }

        return back();
    }
}
