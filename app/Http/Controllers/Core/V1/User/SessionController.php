<?php

namespace App\Http\Controllers\Core\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * SessionController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function destroy(): JsonResponse
    {
        Auth::user()->clearSessions();

        return response()->json(['message' => 'All your sessions have been cleared.']);
    }
}
