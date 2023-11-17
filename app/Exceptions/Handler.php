<?php

namespace App\Exceptions;

use App\Geocode\AddressNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (AddressNotFoundException $e) {
            return response()->json([
                'errors' => [
                    'address_not_found' => [__('validation.custom.address.not_found', ['address' => $e->getMessage()])],
                ],
            ], 422);
        });
    }
}
