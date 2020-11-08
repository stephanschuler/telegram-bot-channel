<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

use function assert;

class EventDispatcher /* implements \Psr\EventDispatcher\EventDispatcherInterface */
{
    protected $consumers = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function dispatch($event): void
    {
        $consumers = [];

        foreach ($this->consumers as $relation) {
            assert($relation instanceof ConsumerRelation);
            $consumer = $relation->getEventConsumer();
            if ($consumer instanceof EventConsumer
                && !isset($consumers[spl_object_id($consumer)])
                && $relation->matchesCondition($event)
            ) {
                $consumers[spl_object_id($consumer)] = $consumer;
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
        $relation = ConsumerRelation::create($consumer)
            ->withCondition($condition);
        $this->consumers[] = $relation;
    }

    public function unregister(EventConsumer $consumer): void
    {
        $this->consumers = array_filter($this->consumers, static function (ConsumerRelation $relation) use ($consumer) {
            $delinquent = $relation->getEventConsumer();
            if (!($delinquent instanceof EventConsumer)) {
                return false;
            }
            return !($delinquent === $consumer);
        });
    }
}