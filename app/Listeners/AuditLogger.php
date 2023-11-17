<?php

namespace App\Listeners;

use App\Events\EndpointHit;
use App\Models\Audit;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditLogger implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(EndpointHit $event): void
    {
        Audit::create([
            'user_id' => $event->getUser()->id ?? null,
            'oauth_client_id' => $event->getOauthClient()->id ?? null,
            'action' => $event->getAction(),
            'description' => $event->getDescription(),
            'ip_address' => $event->getIpAddress(),
            'user_agent' => $event->getUserAgent(),
            'created_at' => $event->getCreatedAt(),
        ]);
    }
}
