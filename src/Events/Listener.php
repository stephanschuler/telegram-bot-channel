<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

interface Listener
{
    public function __invoke($data): void;
}