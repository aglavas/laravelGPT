<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Actions\EmbeddWebV2;
use App\Models\SourceUrl;

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

Route::get('/widget', function () {
    return Inertia::render('Widget/Home');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'urls' => SourceUrl::all()->map(fn (SourceUrl $url) => $url->url)
        ]);
    })->name('dashboard');
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
