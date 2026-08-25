<?php

use Abra\AbraStatamicRedirect\Http\Controllers\CP\RedirectActionController;
use Abra\AbraStatamicRedirect\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::prefix('redirects')->name('abra-statamic-redirects.')->group(function () {
    Route::get('/', [RedirectController::class, 'index'])->name('index');
    Route::get('/json', [RedirectController::class, 'json'])->name('json');
    Route::post('/actions', [RedirectActionController::class, 'run'])->name('actions.run');
    Route::post('/actions/list', [RedirectActionController::class, 'bulkActions'])->name('actions.list');
    Route::get('/create', [RedirectController::class, 'create'])->name('create');
    Route::post('/store', [RedirectController::class, 'store'])->name('store');
    Route::get('{id}/edit', [RedirectController::class, 'edit'])->name('edit');
    Route::patch('{id}', [RedirectController::class, 'update'])->name('update');
    Route::delete('{id}', [RedirectController::class, 'destroy'])->name('destroy');
});
