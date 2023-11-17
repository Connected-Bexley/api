<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexRequest;
use App\Http\Requests\Notification\ShowRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NotificationController extends Controller
{
    /**
     * NotificationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $baseQuery = Notification::query()
            ->orderByDesc('created_at');

        $notifications = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::scope('referral_id'),
                AllowedFilter::scope('service_id'),
                AllowedFilter::scope('user_id'),
            ])
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all notifications'));

        return NotificationResource::collection($notifications);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowRequest $request, Notification $notification): NotificationResource
    {
        $baseQuery = Notification::query()
            ->where('id', $notification->id);

        $notification = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed Notification [{$notification->id}]", $notification));

        return new NotificationResource($notification);
    }
}
