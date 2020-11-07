<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class EventConsumerClosure implements EventConsumer
{
    private $consume;

    private function __construct(callable $consume)
    {
        $this->consume = $consume;
    }

    public static function create($consume): self
    {
        return new static($consume);
    }

    public function consume($data): void
    {
        ($this->consume)($data);
    }
}