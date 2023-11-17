<?php

namespace App\Http\Controllers;

use App\Docs\OpenApi;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\View\View;

class DocsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(): View
    {
        return view('docs.index');
    }

    /**
     * Parse the specified YAML file through Blade.
     *
     * @throws \Throwable
     */
    public function show(): Responsable
    {
        return OpenApi::create();
    }
}
