<?php

use App\Http\Controllers\InspectionController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\RangeImportController;
use App\Http\Controllers\SerialController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/', [SerialController::class, 'index'])->name('serials.index');
    Route::get('/serials/{sn}', [SerialController::class, 'show'])->name('serials.show');

    Route::get('/ranges', [RangeImportController::class, 'index'])->name('ranges.index');
    Route::post('/ranges', [RangeImportController::class, 'store'])->name('ranges.store');

    Route::get('/links', [LinkController::class, 'index'])->name('links.index');
    Route::post('/links', [LinkController::class, 'store'])->name('links.store');
    Route::post('/links/{link}/cancel', [LinkController::class, 'cancel'])->name('links.cancel');

    Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections.index');
    Route::post('/inspections', [InspectionController::class, 'store'])->name('inspections.store');
});
