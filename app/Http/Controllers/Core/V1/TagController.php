<?php

namespace App\Http\Controllers\Core\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\IndexRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    /**
     * TagController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return TagResource::collection(Tag::all());
    }
}
