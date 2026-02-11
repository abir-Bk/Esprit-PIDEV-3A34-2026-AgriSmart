<?php

namespace App\Message;

final class OrderPaidMessage
{
    public function __construct(
        public int $commandeId
    ) {
    }
}
