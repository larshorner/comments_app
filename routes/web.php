<?php

use App\Http\Controllers\CommentsController;
use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/comments',         [CommentsController::class, 'index'])
    ->middleware('auth')->name('comments.index');
Route::get('/comments/create',  [CommentsController::class, 'create'])
    ->middleware('auth')->name('comments.create');
Route::post('/comments/create', [CommentsController::class, 'store'])
    ->middleware('auth');
Route::delete('/comments/{id}', [CommentsController::class, 'destroy'])
    ->middleware('auth')->name('comments.destroy');
Route::get('/comments/like/{id}', [CommentsController::class, 'like'])
    ->middleware('auth')->name('comments.like');

require __DIR__.'/auth.php';
