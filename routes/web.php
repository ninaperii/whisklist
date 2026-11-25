<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AllergenController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DietaryTagController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipeDraftController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::get('/users/{user}', [ProfileController::class, 'show'])
    ->name('users.show');

Route::middleware('auth')->group(function () {
    Route::resource('recipes', RecipeDraftController::class)->only(['create', 'store', 'edit', 'update']);
    Route::get('/my-recipes', [RecipeDraftController::class, 'index'])->name('recipes.mine');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('ingredients', IngredientController::class)->except('show');
        Route::resource('dietary-tags', DietaryTagController::class)->except('show');
        Route::resource('allergens', AllergenController::class)->except('show');
    });
