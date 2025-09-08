<?php

use Illuminate\Support\Facades\Route;

Route::prefix('redirects')->name('abra-statamic-redirects.')->group(function () {
    Route::get('/', [RedirectController::class, 'index'])->name('index');
    Route::get('/create', [RedirectController::class, 'create'])->name('create');
    Route::post('/', [RedirectController::class, 'store'])->name('store');
    Route::get('{id}/edit', [RedirectController::class, 'edit'])->name('edit');
    Route::patch('{id}', [RedirectController::class, 'update'])->name('update');
    Route::delete('{id}', [RedirectController::class, 'destroy'])->name('destroy');
});