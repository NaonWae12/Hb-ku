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
    Route::get('/forms/{form}/responses', [FormController::class, 'responses'])->name('forms.responses');
    Route::get('/forms/{form}/responses/data', [FormController::class, 'responsesData'])->name('forms.responses.data');
    Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
    Route::put('/forms/{form}', [FormController::class, 'update'])->name('forms.update');
    Route::delete('/forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
    Route::delete('/forms/{form}/answer-templates/{template}', [FormController::class, 'destroyAnswerTemplate'])->name('forms.answer-templates.destroy');
    Route::delete('/forms/{form}/result-rules/{rule}', [FormController::class, 'destroyResultRule'])->name('forms.result-rules.destroy');
    Route::post('/form-rule-presets', [FormRulePresetController::class, 'store'])->name('form-rule-presets.store');
    Route::delete('/form-rule-presets/{formRulePreset}', [FormRulePresetController::class, 'destroy'])->name('form-rule-presets.destroy');
});

require __DIR__.'/auth.php';
