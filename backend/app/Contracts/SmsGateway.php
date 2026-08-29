<?php

namespace App\Contracts;

interface SmsGateway
{
    /**
     * Send an SMS. Portfolio stub implementations log and return true.
     */
    public function send(string $to, string $message): bool;
}
