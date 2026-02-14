<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SharingController extends Controller
{
    public function index()
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        // Get all users with access to this budget
        $members = $budget->users()
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
                'avatar' => $this->getInitials($user->name),
                'is_current_user' => $user->id === Auth::id(),
            ]);

        // Get pending invites
        $pendingInvites = $budget->invites()
            ->whereNull('accepted_at')
            ->get()
            ->map(fn($invite) => [
                'id' => $invite->id,
                'email' => $invite->email,
                'created_at' => $invite->created_at->diffForHumans(),
            ]);

        // Check if current user is the owner
        $isOwner = $budget->owner_id === Auth::id();

        return Inertia::render('Settings/Sharing/Index', [
            'members' => $members,
            'pendingInvites' => $pendingInvites,
            'isOwner' => $isOwner,
            'budgetName' => $budget->name,
        ]);
    }

    public function invite(Request $request)
    {
        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return redirect()->route('onboarding.setup');
        }

        // Only owner can invite
        if ($budget->owner_id !== Auth::id()) {
            abort(403, 'Only the budget owner can invite members.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower($validated['email']);

        // Check if already a member
        $existingMember = $budget->users()->where('email', $email)->exists();
        if ($existingMember) {
            return back()->withErrors(['email' => 'This person is already a member of this budget.']);
        }

        // Check if already invited
        $existingInvite = $budget->invites()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->exists();
        if ($existingInvite) {
            return back()->withErrors(['email' => 'An invite has already been sent to this email.']);
        }

        // Create invite
        $invite = Invite::create([
            'budget_id' => $budget->id,
            'email' => $email,
            'invited_by' => Auth::id(),
        ]);

        // In a real app, send email here
        // Mail::to($email)->send(new BudgetInvite($invite));

        return back()->with('success', 'Invite sent successfully.');
    }

    public function cancelInvite(Invite $invite)
    {
        $budget = Auth::user()->currentBudget;

        if ($invite->budget_id !== $budget->id) {
            abort(403);
        }

        // Only owner can cancel invites
        if ($budget->owner_id !== Auth::id()) {
            abort(403, 'Only the budget owner can cancel invites.');
        }

        $invite->delete();

        return back()->with('success', 'Invite cancelled.');
    }

    public function removeMember(User $user)
    {
        $budget = Auth::user()->currentBudget;

        // Only owner can remove members
        if ($budget->owner_id !== Auth::id()) {
            abort(403, 'Only the budget owner can remove members.');
        }

        // Can't remove the owner
        if ($user->id === $budget->owner_id) {
            return back()->withErrors(['error' => 'Cannot remove the budget owner.']);
        }

        // Remove from budget
        $budget->users()->detach($user->id);

        // If this was their current budget, clear it
        if ($user->current_budget_id === $budget->id) {
            $user->update(['current_budget_id' => null]);
        }

        return back()->with('success', 'Member removed from budget.');
    }

    public function pendingInvites()
    {
        $user = Auth::user();

        // Get invites for this user's email
        $invites = Invite::with('budget.owner')
            ->where('email', strtolower($user->email))
            ->whereNull('accepted_at')
            ->get()
            ->map(fn($invite) => [
                'id' => $invite->id,
                'token' => $invite->token,
                'budget_name' => $invite->budget->name,
                'invited_by' => $invite->budget->owner->name,
                'created_at' => $invite->created_at->diffForHumans(),
            ]);

        return Inertia::render('Settings/Sharing/PendingInvites', [
            'invites' => $invites,
        ]);
    }

    public function acceptInvite(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $invite = Invite::where('token', $validated['token'])
            ->whereNull('accepted_at')
            ->firstOrFail();

        $user = Auth::user();

        // Verify email matches (case-insensitive)
        if (strtolower($user->email) !== strtolower($invite->email)) {
            return back()->withErrors(['error' => 'This invite was sent to a different email address.']);
        }

        // Add user to budget
        $invite->budget->users()->attach($user->id, [
            'role' => 'member',
            'invited_at' => $invite->created_at,
            'accepted_at' => now(),
        ]);

        // Mark invite as accepted
        $invite->update(['accepted_at' => now()]);

        // Switch to this budget
        $user->update(['current_budget_id' => $invite->budget_id]);

        return redirect()->route('dashboard')->with('success', 'You have joined the budget!');
    }

    public function declineInvite(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $invite = Invite::where('token', $validated['token'])
            ->whereNull('accepted_at')
            ->firstOrFail();

        $user = Auth::user();

        // Verify email matches
        if (strtolower($user->email) !== strtolower($invite->email)) {
            return back()->withErrors(['error' => 'This invite was sent to a different email address.']);
        }

        // Delete the invite
        $invite->delete();

        return back()->with('success', 'Invite declined.');
    }

    private function getInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }

        return substr($initials, 0, 2);
    }
}
