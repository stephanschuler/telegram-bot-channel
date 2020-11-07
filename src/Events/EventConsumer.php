<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

interface EventConsumer
{
    public function consume($data): void;
}