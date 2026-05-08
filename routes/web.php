<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

// 1. General View Access (60 req/min)
Route::middleware(['throttle:contacts-view'])->group(function () {
    
    Route::get('/', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts', [ContactController::class, 'index']);

    // 2. Strict Data Modification (5 req/min)
    Route::middleware(['throttle:contacts-modify'])->group(function () {
        
        // Create - POST to /contacts
        Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
        
        // Update - PUT to /contacts/{id}
       
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        
        // Delete - DELETE to /contacts/{id}
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        
    });
});