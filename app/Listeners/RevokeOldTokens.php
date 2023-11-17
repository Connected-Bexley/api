<?php

namespace App\Listeners;

use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Token;

class RevokeOldTokens
{
    /**
     * Handle the event.
     */
    public function handle(AccessTokenCreated $event): void
    {
        Token::query()
            ->where('user_id', $event->userId)
            ->where('id', '!=', $event->tokenId)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }
}
