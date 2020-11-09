<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class ConsumerRelation
{
    private $consumer;
    private $condition;

    public function __construct(EventConsumer $consumer, callable $condition)
    {
        $this->consumer = $consumer;
        $this->condition = $condition;
    }

    public static function create(EventConsumer $consumer): self
    {
        return new static($consumer, self::always());
    }

    public function withCondition(callable $condition): self
    {
        return new static($this->consumer, $condition);
    }

    public function getEventConsumer(): ?EventConsumer
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