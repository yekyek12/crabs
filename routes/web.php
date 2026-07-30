<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminCrabSpeciesController;
use App\Http\Controllers\Admin\AdminFeedbackController;
use App\Http\Controllers\Admin\AdminModelController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminScanDataController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CrabChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ModelComparisonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecognitionController;
use App\Http\Controllers\RecognitionExportController;
use App\Http\Controllers\RecognitionHistoryController;
use App\Http\Controllers\RecognitionMapController;
use App\Http\Controllers\ReportsPageController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\TrainingDatasetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/health', HealthController::class)->name('health');
Route::redirect('/species', '/crab-chat');
Route::get('/crab-chat', [CrabChatController::class, 'index'])->name('crab-chat.index');
Route::post('/crab-chat/message', [CrabChatController::class, 'chat'])->middleware('throttle:20,1')->name('crab-chat.message');
Route::get('/species/{crabSpecies}', [SpeciesController::class, 'show'])->name('species.show');
Route::view('/offline', 'offline')->name('offline');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/recognition-map', [RecognitionMapController::class, 'index'])->name('recognition.map');
    Route::get('/reports', [ReportsPageController::class, 'index'])->name('reports.index');
    Route::get('/training-dataset', [TrainingDatasetController::class, 'index'])->name('training.index');
    Route::get('/training-dataset/export', [TrainingDatasetController::class, 'export'])->name('training.export');
    Route::get('/model-comparison', [ModelComparisonController::class, 'index'])->name('models.comparison');
    Route::get('/recognition/create', [RecognitionController::class, 'create'])->name('recognition.create');
    Route::post('/recognition', [RecognitionController::class, 'store'])->middleware('throttle:recognition')->name('recognition.store');
    Route::delete('/recognition', [RecognitionHistoryController::class, 'clear'])->name('recognition.clear');
    Route::get('/history/export/csv', [RecognitionExportController::class, 'csv'])->name('recognition.export.csv');
    Route::get('/history/export/pdf', [RecognitionExportController::class, 'pdf'])->name('recognition.export.pdf');
    Route::get('/recognition/{recognitionRecord}', [RecognitionController::class, 'show'])->name('recognition.show');
    Route::get('/recognition/{recognitionRecord}/image', [RecognitionController::class, 'image'])->name('recognition.image');
    Route::delete('/recognition/{recognitionRecord}', [RecognitionController::class, 'destroy'])->name('recognition.destroy');
    Route::get('/history', [RecognitionHistoryController::class, 'index'])->name('recognition.history');
    Route::post('/recognition/{recognitionRecord}/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('accounts', AdminAccountController::class)->except(['show', 'destroy']);
    Route::get('/scans/export/csv', [AdminScanDataController::class, 'export'])->name('scans.export.csv');
    Route::resource('scans', AdminScanDataController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::resource('species', AdminCrabSpeciesController::class)->except('show');
    Route::get('/feedback', [AdminFeedbackController::class, 'index'])->name('feedback.index');
    Route::patch('/feedback/{recognitionFeedback}', [AdminFeedbackController::class, 'update'])->name('feedback.update');
    Route::get('/models', [AdminModelController::class, 'index'])->name('models.index');
    Route::post('/models', [AdminModelController::class, 'store'])->name('models.store');
    Route::post('/models/sync', [AdminModelController::class, 'sync'])->name('models.sync');
    Route::patch('/models/{modelVersion}/activate', [AdminModelController::class, 'activate'])->name('models.activate');
});
