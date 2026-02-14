<?php

namespace App\Http\Controllers;

use App\Models\MonthlyBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $budget = $user->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        // Get all category groups with categories and their projections
        $categoryGroups = $budget->categoryGroups()
            ->with(['categories' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'categories' => $group->categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'icon' => $category->icon,
                            'default_amount' => (float) ($category->default_amount ?? 0),
                            'projections' => $category->projections ?? [],
                        ];
                    }),
                ];
            });

        // Check if any projections exist
        $hasProjections = $categoryGroups->some(function ($group) {
            return $group['categories']->some(function ($cat) {
                return !empty($cat['projections']);
            });
        });

        return Inertia::render('Plan/Index', [
            'categoryGroups' => $categoryGroups,
            'defaultMonthlyIncome' => (float) ($budget->default_monthly_income ?? 0),
            'hasProjections' => $hasProjections,
        ]);
    }
}