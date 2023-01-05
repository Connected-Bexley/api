<?php

use App\Http\Controllers\Laravel;
use App\Http\Controllers\Passport\AuthorizationController;
use Illuminate\Support\Facades\Route;

Route::get('oauth/authorize', [App\Http\Controllers\Passport\AuthorizationController::class, 'authorize']);
Route::post('oauth/authorize', [Laravel\Passport\Http\Controllers\ApproveAuthorizationController::class, 'approve']);
Route::delete('oauth/authorize', [Laravel\Passport\Http\Controllers\DenyAuthorizationController::class, 'deny']);
