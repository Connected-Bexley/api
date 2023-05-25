<?php

use App\Http\Controllers\Auth;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
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

// Authentication Routes.
Route::get('login', [Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [Auth\LoginController::class, 'login']);
Route::get('login/code', [Auth\LoginController::class, 'showOtpForm'])->name('login.code');
Route::post('login/code', [Auth\LoginController::class, 'otp']);
Route::post('logout', [Auth\LoginController::class, 'logout'])->name('logout');

// Password Reset Routes.
Route::get('password/reset', [Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [Auth\ResetPasswordController::class, 'reset'])->name('password.update');

Route::get('/', HomeController::class)->name('home');

Route::get('/docs', [DocsController::class, 'index'])
    ->name('docs.index');

Route::get('/docs/openapi.json', [DocsController::class, 'show'])
    ->name('docs.show');

Route::get('/sitemap', SitemapController::class)->name('sitemap');
