<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

class Events
{
    private $eventEmitter;
    private $condition;

    private function __construct(EventEmitter $eventEmitter, callable $condition)
    {
        $this->eventEmitter = $eventEmitter;
        $this->condition = $condition;
    }

    public static function create(EventEmitter $eventEmitter): self
    {
        $always = static function ($data) {
            return true;
        };
        return new static($eventEmitter, $always);
    }

    public function filter(callable $condition): self
    {
        $preCondition = $this->condition;
        $filter = static function ($data) use ($preCondition, $condition) {
            return $preCondition($data) && $condition($data);
        };
        return new static($this->eventEmitter, $filter);
    }

    public function register(Listener $listener): callable
    {
        return $this->eventEmitter->register($listener, $this->condition);
    }

    public function unregister(Listener $listener, ?callable $condition = null): void
    {
        $this->eventEmitter->unregister($listener, $condition);
    }
}
