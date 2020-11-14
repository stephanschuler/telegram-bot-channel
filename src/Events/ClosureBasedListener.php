<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class ClosureBasedListener implements Listener
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

    public function __invoke($data): void
    {
        ($this->consume)($data);
    }
}