<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

use SplObjectStorage;
use function assert;
use function is_callable;

class EventDispatcher /* implements \Psr\EventDispatcher\EventDispatcherInterface */
{
    protected $consumers;

    private function __construct()
    {
        $this->consumers = new SplObjectStorage();
    }

    public static function create(): self
    {
        return new self();
    }

    public function dispatch($event): void
    {
        $consumers = [];

        foreach ($this->consumers as $consumer) {
            $conditions = $this->consumers[$consumer];
            foreach ($conditions as $condition) {
                assert(is_callable($condition));
                if ($condition($event)) {
                    $consumers[] = $consumer;
                    break;
                }
            }
        }

        foreach ($consumers as $consumer) {
            assert($consumer instanceof EventConsumer);
            $consumer->consume($event);
        }
    }

    public function getEventEmitter(): EventEmitter
    {
        return EventEmitter::create($this);
    }

    public function register(EventConsumer $consumer, callable $condition): void
    {
        $conditions = $this->consumers[$consumer] ?? [];
        $conditions[] = $condition;
        $this->consumers[$consumer] = $conditions;
    }

    public function unregister(EventConsumer $consumer): void
    {
        unset($this->consumers[$consumer]);
    }
}