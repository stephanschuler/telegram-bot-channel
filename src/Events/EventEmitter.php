<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class EventEmitter
{
    private $eventDispatcher;
    private $condition;

    private function __construct(EventDispatcher $eventDispatcher, callable $condition)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->condition = $condition;
    }

    public static function create(EventDispatcher $eventDispatcher): self
    {
        $always = static function ($data) {
            return true;
        };
        return new static($eventDispatcher, $always);
    }

    public function filter(callable $condition): self
    {
        $preCondition = $this->condition;
        $filter = static function ($data) use ($preCondition, $condition) {
            return $preCondition($data) && $condition($data);
        };
        return new static($this->eventDispatcher, $filter);
    }

    public function register(EventConsumer $consumer): callable
    {
        return $this->eventDispatcher->register($consumer, $this->condition);
    }

    public function unregister(EventConsumer $consumer, ?callable $condition = null): void
    {
        $this->eventDispatcher->unregister($consumer, $condition);
    }
}
