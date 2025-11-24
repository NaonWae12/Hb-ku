<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\FormRulePresetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/f/{form:slug}', [PublicFormController::class, 'show'])->name('forms.public.show');
Route::post('/f/{form:slug}', [PublicFormController::class, 'submit'])->name('forms.public.submit');

Route::get('/dashboard', [FormController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Form routes
    Route::get('/forms/create', [FormController::class, 'create'])->name('forms.create');
    Route::get('/forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
    Route::put('/forms/{form}', [FormController::class, 'update'])->name('forms.update');
    Route::delete('/forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
    Route::post('/form-rule-presets', [FormRulePresetController::class, 'store'])->name('form-rule-presets.store');
});

require __DIR__.'/auth.php';
