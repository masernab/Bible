<?php

use App\Http\Controllers\BibleSearchController;
use App\Http\Controllers\VerseReferenceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('/bible', BibleSearchController::class)->name('bible.search');

Route::get('/passage', VerseReferenceController::class)->name('bible.reference');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
