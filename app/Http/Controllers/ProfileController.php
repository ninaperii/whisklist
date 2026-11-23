<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display a user profile when its privacy rules allow access.
     */
    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        return view('profiles.show', [
            'profile' => $user,
        ]);
    }

    /**
     * Display the authenticated user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        Gate::authorize('update', $user);

        return view('profiles.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        Gate::authorize('update', $user);

        $user->update($request->validated());

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profile updated successfully.');
    }
}
