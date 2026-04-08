<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'username' => $user->username,
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
                'invite_url' => url('/invite/' . $invite->token),
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

        // Create invite
        $invite = Invite::create([
            'budget_id' => $budget->id,
            'invited_by' => Auth::id(),
        ]);

        $inviteUrl = url('/invite/' . $invite->token);

        return back()->with('success', 'Invite created!')->with('inviteUrl', $inviteUrl);
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
        return Inertia::render('Settings/Sharing/PendingInvites', [
            'invites' => [],
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

        // Check if already a member
        if ($invite->budget->users()->where('user_id', $user->id)->exists()) {
            return redirect()->route('dashboard')->with('success', 'You are already a member of this budget.');
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

    public function acceptViaLink(string $token)
    {
        $invite = Invite::where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if (!$invite) {
            return redirect()->route('dashboard')->with('error', 'This invite link is no longer valid.');
        }

        $user = Auth::user();

        // Check if already a member
        if ($invite->budget->users()->where('user_id', $user->id)->exists()) {
            return redirect()->route('dashboard')->with('success', 'You are already a member of this budget.');
        }

        // Show the invite acceptance page
        return Inertia::render('Settings/Sharing/AcceptInvite', [
            'token' => $invite->token,
            'budgetName' => $invite->budget->name,
            'invitedBy' => $invite->budget->owner->name,
        ]);
    }

    public function declineInvite(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $invite = Invite::where('token', $validated['token'])
            ->whereNull('accepted_at')
            ->firstOrFail();

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
