<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create and authenticate a new user account.
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only([
            'name',
            'email',
            'password',
        ]));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
