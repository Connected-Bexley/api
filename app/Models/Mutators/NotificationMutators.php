<?php

namespace App\Models\Mutators;

trait NotificationMutators
{
    public function getRecipientAttribute(string $recipient): string
    {
        return decrypt($recipient);
    }

    public function setRecipientAttribute(string $recipient)
    {
        $this->attributes['recipient'] = encrypt($recipient);
    }

    public function getMessageAttribute(string $message): string
    {
        return decrypt($message);
    }

    public function setMessageAttribute(string $message)
    {
        $this->attributes['message'] = encrypt($message);
    }
}
