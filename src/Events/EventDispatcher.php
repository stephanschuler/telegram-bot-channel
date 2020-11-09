<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel\Events;

use function assert;

class EventDispatcher /* implements \Psr\EventDispatcher\EventDispatcherInterface */
{
    private const DROP = false;
    private const KEEP = true;
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

    public function register(EventConsumer $consumer, callable $condition): callable
    {
        $relation = ConsumerRelation::create($consumer)
            ->withCondition($condition);
        $this->consumers[] = $relation;
        return function () use ($consumer, $condition) {
            $this->unregister($consumer, $condition);
        };
    }

    public function unregister(EventConsumer $consumer, ?callable $condition = null): void
    {
        $this->consumers = array_filter(
            $this->consumers,
            static function (ConsumerRelation $relation) use ($consumer, $condition) {
                $delinquent = $relation->getEventConsumer();
                $delinquentCondition = $relation->getCondition();
                $relationIsExpired = !($delinquent instanceof EventConsumer);
                switch (true) {
                    case ($relationIsExpired):
                    case ($consumer === $delinquent && $condition === $delinquentCondition):
                    case ($consumer === $delinquent && $condition === null):
                        return self::DROP;
                }
                return self::KEEP;
            }
        );
    }
}