<?php

namespace App\Http\Controllers;

use App\Models\Payee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PayeeController extends Controller
{
    public function index()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        $payees = $budget->payees()
            ->with('defaultCategory')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'default_category_id' => $p->default_category_id,
                'default_category_name' => $p->defaultCategory?->name,
                'transaction_count' => $p->transactions()->count(),
            ]);

        $categories = $budget->categoryGroups()
            ->with(['categories' => fn($q) => $q->where('is_hidden', false)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'categories' => $group->categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'icon' => $c->icon,
                ]),
            ]);

        return Inertia::render('Settings/Payees/Index', [
            'payees' => $payees,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Payee $payee)
    {
        $budget = Auth::user()->currentBudget;

        // Ensure payee belongs to user's budget
        if ($payee->budget_id !== $budget->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_category_id' => 'nullable|exists:categories,id',
        ]);

        $payee->update($validated);

        return back()->with('success', 'Payee updated');
    }

    public function destroy(Payee $payee)
    {
        $budget = Auth::user()->currentBudget;

        // Ensure payee belongs to user's budget
        if ($payee->budget_id !== $budget->id) {
            abort(403);
        }

        // Check if payee has transactions
        if ($payee->transactions()->exists()) {
            return back()->withErrors(['payee' => 'Cannot delete payee with existing transactions.']);
        }

        $payee->delete();

        return back()->with('success', 'Payee deleted');
    }
}
