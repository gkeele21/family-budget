<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\MonthlyBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BudgetController extends Controller
{
    /**
     * Calculate the average spent for all categories over the calendar year (excluding current month).
     * Returns a map of category_id => average_spent.
     */
    private function calculateAverageSpentBulk(array $categoryIds, string $currentMonth): array
    {
        // Get the year from the current month
        $year = date('Y', strtotime($currentMonth . '-01'));

        // Start of the year
        $startDate = $year . '-01-01';

        // End of the month before the current month (or end of previous year if viewing January)
        $currentMonthNum = (int) date('m', strtotime($currentMonth . '-01'));
        if ($currentMonthNum === 1) {
            // If viewing January, look at previous year
            $prevYear = $year - 1;
            $startDate = $prevYear . '-01-01';
            $endDate = $prevYear . '-12-31';
        } else {
            // End of the previous month within the same year
            $endDate = date('Y-m-t', strtotime($currentMonth . '-01 -1 month'));
        }

        // Single query to get total spent per category in the date range
        $spentData = \App\Models\Transaction::whereIn('category_id', $categoryIds)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('category_id, SUM(ABS(amount)) as total_spent, COUNT(DISTINCT DATE_FORMAT(date, "%Y-%m")) as months_with_data')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $result = [];
        foreach ($categoryIds as $categoryId) {
            $data = $spentData->get($categoryId);
            if ($data && $data->months_with_data > 0) {
                $result[$categoryId] = round($data->total_spent / $data->months_with_data, 2);
            } else {
                $result[$categoryId] = 0;
            }
        }

        return $result;
    }

    public function edit()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        return Inertia::render('Budgets/Edit', [
            'budget' => [
                'id' => $budget->id,
                'name' => $budget->name,
                'start_month' => $budget->start_month,
                'default_monthly_income' => (float) ($budget->default_monthly_income ?? 0),
            ],
        ]);
    }

    public function updateBudget(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_month' => 'nullable|string|date_format:Y-m',
            'default_monthly_income' => 'nullable|numeric|min:0',
        ]);

        $budget->update($validated);

        return redirect()->route('settings.index');
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
            return redirect()->route('onboarding.setup');
        }

        $month = $month ?? now()->format('Y-m');

        // Get all category groups with categories and their monthly budget data
        $groups = $budget->categoryGroups()
            ->with(['categories' => function ($query) use ($month) {
                $query->orderBy('sort_order')
                    ->with(['monthlyBudgets' => function ($q) use ($month) {
                        $q->where('month', $month);
                    }]);
            }])
            ->orderBy('sort_order')
            ->get();

        // Collect all category IDs for bulk average calculation
        $allCategoryIds = $groups->pluck('categories')->flatten()->pluck('id')->toArray();

        // Calculate average spent for all categories in one query (calendar year)
        $avgSpentMap = $this->calculateAverageSpentBulk($allCategoryIds, $month);

        $categoryGroups = $groups->map(function ($group) use ($month, $avgSpentMap) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'categories' => $group->categories->map(function ($category) use ($month, $avgSpentMap) {
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
                            'default_amount' => (float) ($category->default_amount ?? 0),
                            'avg_spent' => $avgSpentMap[$category->id] ?? 0,
                            'projections' => $category->projections ?? [],
                        ];
                    }),
                ];
            });

        // Calculate totals
        $totalBudgeted = $categoryGroups->sum(fn($g) => $g['categories']->sum('budgeted'));
        $totalSpent = $categoryGroups->sum(fn($g) => $g['categories']->sum('spent'));
        $totalAvailable = $totalBudgeted - $totalSpent;

        // Calculate "Ready to Assign" with month-aware breakdown
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Sum of all account starting balances
        $totalStartingBalances = $budget->accounts()->sum('starting_balance');

        // Category IDs for budgeted queries
        $budgetCategoryIds = $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->pluck('id')
            ->toArray();

        // Prior months: starting balances + income - budgeted
        // Expenses don't affect Ready to Assign — they come out of category envelopes
        $priorIncome = $budget->transactions()
            ->where('type', 'income')
            ->where('date', '<', $monthStart)
            ->sum('amount');

        $priorBudgeted = MonthlyBudget::whereIn('category_id', $budgetCategoryIds)
            ->where('month', '<', $month)
            ->sum('budgeted_amount');

        $carriedForward = $totalStartingBalances + $priorIncome - $priorBudgeted;

        // This month's income
        $thisMonthIncome = $budget->transactions()
            ->where('type', 'income')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        // This month's budgeted (already have as $totalBudgeted)

        // Is this the first month? (no prior income or budgets)
        $hasPriorActivity = $priorIncome != 0 || $priorBudgeted != 0;

        // Ready to assign = carried forward + this month's income - this month's budgeted
        $toBudget = $carriedForward + $thisMonthIncome - $totalBudgeted;

        // Earliest month: user-set start month, or fallback to first account creation
        $earliestMonth = $budget->start_month;
        if (!$earliestMonth) {
            $firstAccount = $budget->accounts()->orderBy('created_at')->first();
            $earliestMonth = $firstAccount ? $firstAccount->created_at->format('Y-m') : $month;
        }

        return Inertia::render('Budget/Index', [
            'month' => $month,
            'categoryGroups' => $categoryGroups,
            'summary' => [
                'toBudget' => (float) $toBudget,
                'budgeted' => (float) $totalBudgeted,
                'spent' => (float) $totalSpent,
                'available' => (float) $totalAvailable,
                'carriedForward' => (float) $carriedForward,
                'thisMonthIncome' => (float) $thisMonthIncome,
                'isFirstMonth' => !$hasPriorActivity,
            ],
            'earliestMonth' => $earliestMonth,
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

    /**
     * Copy budget amounts from the previous month to the current month.
     */
    public function copyLastMonth(Request $request, string $month)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        // Calculate previous month
        $previousMonth = date('Y-m', strtotime($month . '-01 -1 month'));

        // Get all category IDs for this budget
        $categoryIds = $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->pluck('id')
            ->toArray();

        // Get previous month's budgets
        $previousBudgets = MonthlyBudget::whereIn('category_id', $categoryIds)
            ->where('month', $previousMonth)
            ->get()
            ->keyBy('category_id');

        // Copy to current month
        foreach ($categoryIds as $categoryId) {
            $previousAmount = $previousBudgets->get($categoryId)?->budgeted_amount ?? 0;

            MonthlyBudget::updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'month' => $month,
                ],
                [
                    'budgeted_amount' => $previousAmount,
                ]
            );
        }

        return back()->with('success', 'Copied budget from ' . date('F Y', strtotime($previousMonth . '-01')));
    }

    /**
     * Move money between categories (adjust budget amounts).
     */
    public function moveMoney(Request $request, string $month)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        $validated = $request->validate([
            'from_category_id' => 'required|exists:categories,id',
            'to_category_id' => 'required|exists:categories,id|different:from_category_id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Get or create monthly budgets for both categories
        $fromBudget = MonthlyBudget::firstOrCreate(
            ['category_id' => $validated['from_category_id'], 'month' => $month],
            ['budgeted_amount' => 0]
        );

        $toBudget = MonthlyBudget::firstOrCreate(
            ['category_id' => $validated['to_category_id'], 'month' => $month],
            ['budgeted_amount' => 0]
        );

        // Move the money
        $fromBudget->budgeted_amount -= $validated['amount'];
        $fromBudget->save();

        $toBudget->budgeted_amount += $validated['amount'];
        $toBudget->save();

        return back()->with('success', 'Moved $' . number_format($validated['amount'], 2));
    }

    /**
     * Apply category default amounts to the current month's budget.
     */
    public function applyDefaults(Request $request, string $month)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        // Get all categories with default amounts
        $categories = $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten();

        foreach ($categories as $category) {
            if ($category->default_amount > 0) {
                MonthlyBudget::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'month' => $month,
                    ],
                    [
                        'budgeted_amount' => $category->default_amount,
                    ]
                );
            }
        }

        return back()->with('success', 'Applied default amounts');
    }

    /**
     * Apply projections to the current month's budget.
     */
    public function applyProjections(Request $request, string $month)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        $validated = $request->validate([
            'projection_index' => 'required|integer|min:1|max:3',
        ]);

        $projectionIndex = (string) $validated['projection_index'];

        // Get all categories with projections
        $categories = $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten();

        foreach ($categories as $category) {
            $projections = $category->projections ?? [];
            $amount = $projections[$projectionIndex] ?? $category->default_amount ?? 0;

            if ($amount > 0) {
                MonthlyBudget::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'month' => $month,
                    ],
                    [
                        'budgeted_amount' => $amount,
                    ]
                );
            }
        }

        return back()->with('success', 'Applied projection ' . $projectionIndex);
    }

    /**
     * Save projections for categories.
     */
    public function saveProjections(Request $request)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        $validated = $request->validate([
            'projections' => 'required|array',
            'projections.*.category_id' => 'required|exists:categories,id',
            'projections.*.values' => 'required|array',
        ]);

        foreach ($validated['projections'] as $item) {
            $category = \App\Models\Category::find($item['category_id']);
            if ($category) {
                $category->projections = $item['values'];
                $category->save();
            }
        }

        return back()->with('success', 'Projections saved');
    }

    /**
     * Clear all projections for the budget.
     */
    public function clearProjections(Request $request)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        // Clear projections on all categories
        $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->each(function ($category) {
                $category->projections = null;
                $category->save();
            });

        return back()->with('success', 'All projections cleared');
    }

    /**
     * Clear all budget amounts for a given month.
     */
    public function clearBudget(Request $request, string $month)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        $categoryIds = $budget->categoryGroups()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->pluck('id')
            ->toArray();

        MonthlyBudget::whereIn('category_id', $categoryIds)
            ->where('month', $month)
            ->update(['budgeted_amount' => 0]);

        return back()->with('success', 'Budget cleared for ' . date('F Y', strtotime($month . '-01')));
    }

    /**
     * Show category detail with transactions for a specific month.
     */
    public function categoryDetail(Request $request, string $month, int $category)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            abort(404);
        }

        // Get the category
        $categoryModel = \App\Models\Category::whereHas('group', function ($q) use ($budget) {
            $q->where('budget_id', $budget->id);
        })->findOrFail($category);

        // Calculate month date range
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Get budget amounts
        $budgeted = $categoryModel->getBudgetedForMonth($month);
        $spent = $categoryModel->getSpentForMonth($month);
        $available = $budgeted - $spent;

        // Get transactions for this category in this month
        $transactions = $budget->transactions()
            ->with(['account', 'payee'])
            ->where(function ($query) use ($category) {
                // Direct category match
                $query->where('category_id', $category)
                    // Or split transactions that include this category
                    ->orWhereHas('splits', function ($q) use ($category) {
                        $q->where('category_id', $category);
                    });
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) use ($category) {
                // For split transactions, show only the portion for this category
                $amount = $t->amount;
                if ($t->isSplit()) {
                    $splitForCategory = $t->splits->firstWhere('category_id', $category);
                    $amount = $splitForCategory ? $splitForCategory->amount : 0;
                }

                return [
                    'id' => $t->id,
                    'date' => $t->date->format('Y-m-d'),
                    'payee' => $t->payee?->name ?? 'Unknown',
                    'account' => $t->account->name,
                    'amount' => (float) $amount,
                    'type' => $t->type,
                    'cleared' => $t->cleared,
                    'memo' => $t->memo,
                    'is_split' => $t->isSplit(),
                ];
            });

        return Inertia::render('Budget/CategoryDetail', [
            'month' => $month,
            'category' => [
                'id' => $categoryModel->id,
                'name' => $categoryModel->name,
                'icon' => $categoryModel->icon,
                'budgeted' => (float) $budgeted,
                'spent' => (float) $spent,
                'available' => (float) $available,
            ],
            'transactions' => $transactions,
        ]);
    }
}
