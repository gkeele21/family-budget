<?php

namespace App\Http\Controllers;

use Database\Seeders\TutorialBudgetSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TutorialController extends Controller
{
    public function hub()
    {
        $user = Auth::user();

        return Inertia::render('Tutorial/Hub', [
            'hasCompletedLearn' => $user->has_completed_learn_tutorial,
            'hasCompletedSetup' => $user->has_completed_setup_tutorial,
        ]);
    }

    public function startLearn()
    {
        $user = Auth::user();

        // Create tutorial budget via seeder
        $seeder = new TutorialBudgetSeeder();
        $budget = $seeder->run($user);

        // Set as current budget and start tutorial
        $user->update([
            'current_budget_id' => $budget->id,
            'tutorial_track' => 'learn',
            'tutorial_step' => 'welcome',
        ]);

        return redirect()->route('budget.index');
    }

    public function startSetup()
    {
        $user = Auth::user();

        $user->update([
            'tutorial_track' => 'setup',
            'tutorial_step' => 'name-budget',
        ]);

        return redirect()->route('onboarding.setup');
    }

    public function updateStep(Request $request)
    {
        $validated = $request->validate([
            'step' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $user->update([
            'tutorial_step' => $validated['step'],
        ]);

        return response()->json(['step' => $validated['step']]);
    }

    public function complete(Request $request)
    {
        $validated = $request->validate([
            'track' => 'required|string|in:learn,setup',
        ]);

        $user = Auth::user();

        $field = $validated['track'] === 'learn'
            ? 'has_completed_learn_tutorial'
            : 'has_completed_setup_tutorial';

        $user->update([
            $field => true,
            'tutorial_track' => null,
            'tutorial_step' => null,
        ]);

        return response()->json(['completed' => $validated['track']]);
    }

    public function tips()
    {
        return Inertia::render('Tutorial/Tips');
    }

    public function tipShow(string $slug)
    {
        return Inertia::render('Tutorial/TipDetail', [
            'slug' => $slug,
        ]);
    }
}
