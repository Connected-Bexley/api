<?php

namespace App\Contracts;

use App\Emails\Email;

interface EmailSender
{
    public function send(Email $email);
}
