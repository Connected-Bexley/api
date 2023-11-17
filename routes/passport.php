<?php

use Illuminate\Support\Facades\Route;

Route::get('oauth/authorize', [\App\Http\Controllers\Passport\AuthorizationController::class, 'authorize'])
    ->name('authorizations.authorize');
Route::post('oauth/authorize', [\Laravel\Passport\Http\Controllers\ApproveAuthorizationController::class, 'approve'])
    ->name('authorizations.approve');
Route::delete('oauth/authorize', [\Laravel\Passport\Http\Controllers\DenyAuthorizationController::class, 'deny'])
    ->name('authorizations.delete');
