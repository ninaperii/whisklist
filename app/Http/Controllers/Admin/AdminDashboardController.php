<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display an overview of the administration area.
     */
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::query()->count(),
            'administratorCount' => User::query()->where('role', UserRole::Admin)->count(),
            'publicProfileCount' => User::query()->where('is_profile_public', true)->count(),
        ]);
    }
}
