<?php

namespace App\Http\Controllers;

use App\Models\CategoryGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryGroupController extends Controller
{
    public function store(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxOrder = $budget->categoryGroups()->max('sort_order') ?? 0;

        CategoryGroup::create([
            'budget_id' => $budget->id,
            'name' => $validated['name'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    public function update(Request $request, CategoryGroup $categoryGroup)
    {
        $this->authorize('update', $categoryGroup);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $categoryGroup->update($validated);

        return back();
    }

    public function destroy(CategoryGroup $categoryGroup)
    {
        $this->authorize('delete', $categoryGroup);

        $categoryGroup->delete();

        return back();
    }

    public function reorder(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('budgets.create');
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:category_groups,id',
        ]);

        $groupIds = $budget->categoryGroups()->pluck('id')->toArray();
        foreach ($validated['ids'] as $id) {
            if (!in_array($id, $groupIds)) {
                abort(403);
            }
        }

        foreach ($validated['ids'] as $index => $id) {
            CategoryGroup::where('id', $id)->update(['sort_order' => $index]);
        }

        return back();
    }
}
