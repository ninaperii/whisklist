<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class SiteNavigation extends Component
{
    /**
     * Create the component instance.
     */
    public function __construct(public string $context = 'header')
    {
        //
    }

    /**
     * Get the navigation links available to the current visitor.
     *
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    public function links(): array
    {
        $links = [
            [
                'label' => 'Home',
                'url' => route('home'),
                'active' => request()->routeIs('home'),
            ],
        ];

        if (Auth::check()) {
            $links[] = [
                'label' => 'Dashboard',
                'url' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
            ];
            $links[] = [
                'label' => 'Profile',
                'url' => route('users.show', Auth::id()),
                'active' => request()->routeIs('users.show', 'profile.*'),
            ];
        }

        return $links;
    }

    /**
     * Get the view that represents the component.
     */
    public function render(): View
    {
        return view('components.site-navigation');
    }
}
