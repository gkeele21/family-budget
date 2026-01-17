<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $validated = $request->validate([
            'group_id' => 'required|exists:category_groups,id',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:10',
            'default_amount' => 'nullable|numeric|min:0',
        ]);

        $maxOrder = Category::where('group_id', $validated['group_id'])->max('sort_order') ?? 0;

        Category::create([
            'group_id' => $validated['group_id'],
            'name' => $validated['name'],
            'icon' => $validated['icon'],
            'default_amount' => $validated['default_amount'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:10',
            'default_amount' => 'nullable|numeric|min:0',
            'group_id' => 'nullable|exists:category_groups,id',
            'sort_order' => 'nullable|integer',
            'is_hidden' => 'boolean',
        ]);

        $category->update($validated);

        return back();
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return back();
    }
}
