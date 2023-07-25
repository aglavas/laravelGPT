<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Actions\EmbeddWebV2;
use App\Models\SourceUrl;
use App\Http\Controllers\ShowWidgetConversationController;
use App\Http\Controllers\HandlePromptController;
use App\Http\Controllers\ShowDashboardController;
use App\Http\Controllers\ShowDashboardConversationController;
use App\Http\Controllers\UpdateDashboardConversationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/embed', function (Request $request, EmbeddWebV2 $embedWeb, SourceUrl $sourceUrl) {
    if (app()->isProduction()) {
        return;
    }

    $url = $request->input('url', null);
    $embedWeb->handle($url);
    $sourceUrl->create([
        'url' => $url
    ]);
});

Route::prefix('widget')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Widget/Widget', [
            'conversation' => null
        ]);
    });

    Route::get('conversation/{id}', ShowWidgetConversationController::class)->name('widget.conversation.show');
    Route::post('conversation/{id}', HandlePromptController::class)->name('widget.conversation.prompt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ShowDashboardController::class)->name('dashboard');
    Route::get('/dashboard/conversation/{conversation}', ShowDashboardConversationController::class)->name('dashboard.conversation.show');
    Route::post('/dashboard/conversation/{conversation}', UpdateDashboardConversationController::class)->name('dashboard.conversation.update');
    Route::post('/context', function (Request $request, EmbeddWebV2 $embedWeb, SourceUrl $sourceUrl) {
        $url = $request->input('url', null);
        $embedWeb->handle($url);
        $sourceUrl->create([
            'url' => $url
        ]);
        return redirect()->route('dashboard');
    })->name('context.create');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
