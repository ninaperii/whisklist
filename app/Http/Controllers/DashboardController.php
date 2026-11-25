<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return view('dashboard', [
            'user' => $user,
            'draftCount' => $user->recipes()->draft()->count(),
            'publishedCount' => $user->recipes()->published()->count(),
            'profileVisibility' => $user->is_profile_public ? 'Public' : 'Private',
        ]);
    }
}
