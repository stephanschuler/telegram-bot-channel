<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class Binding
{
    private $consumer;
    private $condition;

    public function __construct(Listener $consumer, callable $condition)
    {
        $this->consumer = $consumer;
        $this->condition = $condition;
    }

    public static function create(Listener $consumer): self
    {
        return new static($consumer, self::always());
    }

    public function withCondition(callable $condition): self
    {
        return new static($this->consumer, $condition);
    }

    public function getListener(): ?Listener
    {
        return $this->consumer;
    }

    public function getCondition(): callable
    {
        return $this->condition;
    }

    public function matchesCondition($event): bool
    {
        return ($this->condition)($event);
    }

    private static function always(): callable
    {
        return static function () {
            return true;
        };
    }
}